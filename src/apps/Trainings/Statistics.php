<?php

namespace Hubleto\App\Custom\Trainings;

use Hubleto\App\Custom\Trainings\Questionnaire;
use Hubleto\App\Custom\Trainings\Models\QuestionnaireAnswer;
use Hubleto\App\Custom\Trainings\Models\Applicant;

/**
 * Aggregates satisfaction-questionnaire answers for a training. Kept separate
 * from the generic api/get-chart-data endpoint, which only supports a single
 * groupBy + aggregate and cannot produce an eleven-question comparison.
 */
class Statistics extends \Hubleto\Erp\Core
{
  /**
   * Per-question averages for one training, as a chart.js-ready payload
   * ({labels, values, colors}) plus the raw rows for CSV export.
   */
  public function forTraining(int $idTraining): array
  {
    $applicantIds = $this->getModel(Applicant::class)->record
      ->whereHas('TRAINING_DATE', fn($q) => $q->where('id_training', $idTraining))
      ->pluck('id');

    /** @var QuestionnaireAnswer */
    $mAnswer = $this->getModel(QuestionnaireAnswer::class);
    $answers = $mAnswer->record->whereIn('id_applicant', $applicantIds)->get();

    $labels = [];
    $values = [];
    $colors = [];
    $averages = [];

    foreach (Questionnaire::QUESTIONS as $code => $label) {
      $ratings = $answers->pluck($code)->filter(fn($v) => $v !== null)->all();
      $avg = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
      $labels[] = $this->translate($label);
      $values[] = $avg;
      $colors[] = 'rgb(59, 130, 246)';
      $averages[$code] = $avg;
    }

    $freeText = [];
    foreach (Questionnaire::FREE_TEXT_QUESTIONS as $code => $label) {
      $freeText[$code] = $answers->pluck($code)->filter(fn($v) => !empty($v))->values()->all();
    }

    return [
      'chart' => [ 'labels' => $labels, 'values' => $values, 'colors' => $colors ],
      'responseCount' => $answers->count(),
      'averages' => $averages,
      'freeText' => $freeText,
      'rows' => $answers->map(function ($a) {
        $row = [ 'id_applicant' => $a->id_applicant, 'filled_on' => $a->filled_on ];
        foreach (Questionnaire::QUESTIONS as $code => $label) $row[$code] = $a->{$code};
        foreach (Questionnaire::FREE_TEXT_QUESTIONS as $code => $label) $row[$code] = $a->{$code};
        return $row;
      })->all(),
    ];
  }

  /**
   * Renders the statistics rows as UTF-8 CSV (with BOM), unlike the built-in
   * table export which forces windows-1250.
   */
  public function toCsv(int $idTraining): string
  {
    $data = $this->forTraining($idTraining);

    $header = array_merge(['id_applicant', 'filled_on'], array_keys(Questionnaire::QUESTIONS), array_keys(Questionnaire::FREE_TEXT_QUESTIONS));

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
