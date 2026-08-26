<?php

namespace Hubleto\App\Custom\Orders\Controllers\Api;

use Hubleto\App\Custom\Orders\Models\Order;

class ImportOrderWorkers extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    $commit = $this->router()->urlParamAsBool('commit');
    if ($idOrder <= 0) throw new \Exception('idOrder is required.');

    /** @var Order */
    $mOrder = $this->getModel(Order::class);
    $order = $mOrder->record->find($idOrder);
    if (!$order) throw new \Exception('Order not found.');
    if (empty($order->file_workers)) throw new \Exception('No attendees file has been uploaded for this order.');

    $filePath = $this->env()->uploadFolder . '/' . $order->file_workers;

    $importer = $this->getService(\Hubleto\App\Custom\Trainings\WorkersImporter::class);
    $result = $commit ? $importer->import($idOrder, $filePath) : $importer->preview($filePath);

    if ($commit) {
      $mOrder->recalculateTotals($idOrder);
    }

    return [ 'status' => 'success', 'commit' => $commit ] + $result;
  }
}
