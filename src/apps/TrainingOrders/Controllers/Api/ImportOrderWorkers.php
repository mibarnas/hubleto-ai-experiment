<?php

namespace Hubleto\App\Custom\TrainingOrders\Controllers\Api;

use Hubleto\App\Custom\TrainingOrders\Models\Order;
use Hubleto\App\Custom\TrainingOrders\WorkersImporter;

class ImportOrderWorkers extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    $commit = $this->router()->urlParamAsBool('commit');
    if ($idOrder <= 0) throw new \Exception($this->translate('idOrder is required.'));

    /** @var Order */
    $mOrder = $this->getModel(Order::class);
    $order = $mOrder->record->find($idOrder);
    if (!$order) throw new \Exception($this->translate('Order not found.'));
    if (empty($order->file_workers)) throw new \Exception($this->translate('No workers file has been uploaded for this order.'));

    $filePath = $this->env()->uploadFolder . '/' . $order->file_workers;

    // The importer lives in this app -- it used to be looked up under the
    // Trainings namespace, where the class does not exist, so every import
    // failed before it started.
    $importer = $this->getService(WorkersImporter::class);

    $result = $commit
      ? $importer->import($idOrder, $filePath)
      : $importer->preview($filePath)
    ;

    return [ 'status' => 'success', 'commit' => $commit ] + $result;
  }
}
