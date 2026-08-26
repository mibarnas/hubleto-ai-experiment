<?php

namespace Hubleto\App\Custom\Trainings;

use Hubleto\App\Custom\Questionnaires\Questions;
use Hubleto\App\Custom\Questionnaires\Models\Questionnaire;
use Hubleto\App\Custom\Trainings\Models\Attendee;

/**
 * Aggregates satisfaction answers for a training.
 *
 * Reads the questionnaire's rating columns rather than its json_answers blob so
 * the averages, the 1-5 distribution and the date-interval filter stay indexed
 * queries; the JSON is kept only as the raw submitted payload.
 */
class Statistics extends \Hubleto\Erp\Core
{
  /**
   * @param string $dateFrom Filters on when the questionnaire was filled in.
   */
  public function forTraining(int $idTraining, string $dateFrom = '', string $dateTo = ''): array
  {
    $questionnaireIds = $this->getModel(Attendee::class)->record
      ->whereHas('SCHEDULE', fn($q) => $q->where('id_training', $idTraining))
      ->whereNotNull('id_questionnaire')
      ->pluck('id_questionnaire');

    /** @var Questionnaire */
    $mQuestionnaire = $this->getModel(Questionnaire::class);
    $query = $mQuestionnaire->record->whereIn('id', $questionnaireIds);

    if ($dateFrom !== '') $query = $query->where('date_filled', '>=', $dateFrom . ' 00:00:00');
    if ($dateTo !== '') $query = $query->where('date_filled', '<=', $dateTo . ' 23:59:59');

    $answers = $query->get();

    $labels = [];
    $values = [];
    $colors = [];
    $averages = [];
    $distribution = [];

    foreach (Questions::RATINGS as $code => $label) {
      $ratings = $answers->pluck($code)->filter(fn($v) => $v !== null)->all();
      $avg = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;

      $labels[] = $this->translate($label);
      $values[] = $avg;
      $colors[] = 'rgb(59, 130, 246)';
      $averages[$code] = $avg;

      $counts = array_fill(1, 5, 0);
      foreach ($ratings as $rating) {
        if ($rating >= 1 && $rating <= 5) $counts[(int) $rating]++;
      }
      $distribution[$code] = $counts;
    }

    $freeText = [];
    foreach (Questions::FREE_TEXTS as $code => $label) {
      $freeText[$code] = $answers->pluck($code)->filter(fn($v) => !empty($v))->values()->all();
    }

    return [
      'chart' => [ 'labels' => $labels, 'values' => $values, 'colors' => $colors ],
      'responseCount' => $answers->count(),
      'averages' => $averages,
      'distribution' => $distribution,
      'freeText' => $freeText,
      'rows' => $answers->map(function ($a) {
        $row = [ 'id_questionnaire' => $a->id, 'date_filled' => $a->date_filled ];
        foreach (array_keys(Questions::RATINGS) as $code) $row[$code] = $a->{$code};
        foreach (array_keys(Questions::FREE_TEXTS) as $code) $row[$code] = $a->{$code};
        return $row;
      })->all(),
    ];
  }

  /** UTF-8 with BOM, unlike the built-in export which forces windows-1250. */
  public function toCsv(int $idTraining, string $dateFrom = '', string $dateTo = ''): string
  {
    $data = $this->forTraining($idTraining, $dateFrom, $dateTo);

    $header = array_merge(
      ['id_questionnaire', 'date_filled'],
      array_keys(Questions::RATINGS),
      array_keys(Questions::FREE_TEXTS)
    );

    $out = fopen('php://temp', 'r+');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($data['rows'] as $row) {
      fputcsv($out, array_map(fn($k) => $row[$k] ?? '', $header));
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);

    return $csv;
  }
}
