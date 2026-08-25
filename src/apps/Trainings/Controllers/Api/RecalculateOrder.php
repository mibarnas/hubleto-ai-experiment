<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\TrainingOrder;

class RecalculateOrder extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    if ($idOrder <= 0) throw new \Exception('idOrder is required.');

    $this->getModel(TrainingOrder::class)->recalculateTotals($idOrder);

    return [ 'status' => 'success' ];
  }
}
