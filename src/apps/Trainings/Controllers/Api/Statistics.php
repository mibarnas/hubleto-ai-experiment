<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

class Statistics extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idTraining = $this->router()->urlParamAsInteger('idTraining');
    if ($idTraining <= 0) throw new \Exception('idTraining is required.');

    $data = $this->getService(\Hubleto\App\Custom\Trainings\Statistics::class)->forTraining($idTraining);

    return [
      'status' => 'success',
      'data' => $data['chart'],
      'legend' => [ 'display' => false ],
      'responseCount' => $data['responseCount'],
      'averages' => $data['averages'],
      'freeText' => $data['freeText'],
    ];
  }
}
