<?php

namespace Hubleto\App\Custom\Orders\Controllers\Api;

use Hubleto\App\Custom\Orders\Models\Order;

class RecalculateOrder extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    if ($idOrder <= 0) throw new \Exception('idOrder is required.');

    $this->getModel(Order::class)->recalculateTotals($idOrder);

    return [ 'status' => 'success' ];
  }
}
