<?php

namespace Hubleto\App\Custom\Trainings;

use Hubleto\App\Custom\Trainings\Questionnaire;
use Hubleto\App\Custom\Trainings\Models\QuestionnaireAnswer;
use Hubleto\App\Custom\Trainings\Models\Applicant;
use Hubleto\App\Custom\Trainings\Models\TrainingDate;

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
      'distribution' => $this->distribution($answers),
      'trend' => $this->trend($idTraining),
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
   * How many times each rating 1..5 was given, per question. Complements the
   * averages -- two questions can share a mean and have very different spreads.
   *
   * @return array<string, array<int, int>>
   */
  private function distribution(mixed $answers): array
  {
    $distribution = [];

    foreach (array_keys(Questionnaire::QUESTIONS) as $code) {
      $counts = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ];
      foreach ($answers->pluck($code)->all() as $rating) {
        $rating = (int) $rating;
        if ($rating >= 1 && $rating <= 5) $counts[$rating]++;
      }
      $distribution[$code] = $counts;
    }

    return $distribution;
  }

  /**
   * Overall-satisfaction average per training date, oldest first, so the
   * Statistics tab can show whether a training is improving over time.
   *
   * @return array{labels: string[], values: float[], colors: string[], counts: int[]}
   */
  private function trend(int $idTraining): array
  {
    /** @var TrainingDate */
    $mTrainingDate = $this->getModel(TrainingDate::class);
    $dates = $mTrainingDate->record
      ->where('id_training', $idTraining)
      ->orderBy('datetime_start')
      ->get()
    ;

    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    /** @var QuestionnaireAnswer */
    $mAnswer = $this->getModel(QuestionnaireAnswer::class);

    $labels = [];
    $values = [];
    $colors = [];
    $counts = [];

    foreach ($dates as $date) {
      $applicantIds = $mApplicant->record->where('id_training_date', $date->id)->pluck('id');
      $ratings = $mAnswer->record
        ->whereIn('id_applicant', $applicantIds)
        ->pluck('q_overall_satisfaction')
        ->filter(fn($v) => $v !== null)
        ->all()
      ;

      $labels[] = date('Y-m-d', strtotime($date->datetime_start));
      $values[] = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
      $colors[] = 'rgb(34, 197, 94)';
      $counts[] = count($ratings);
    }

    return [ 'labels' => $labels, 'values' => $values, 'colors' => $colors, 'counts' => $counts ];
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
