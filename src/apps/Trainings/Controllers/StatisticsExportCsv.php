<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class StatisticsExportCsv extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  public function render(): string
  {
    $idTraining = $this->router()->urlParamAsInteger('idTraining');
    if ($idTraining <= 0) {
      http_response_code(400);
      return 'idTraining is required.';
    }

    $csv = $this->getService(\Hubleto\App\Custom\Trainings\Statistics::class)->toCsv($idTraining);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="training-' . $idTraining . '-questionnaire.csv"');
    header('Content-Length: ' . strlen($csv));

    return $csv;
  }
}
