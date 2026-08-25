<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\TrainingOrder;

class ImportApplicants extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    $commit = $this->router()->urlParamAsBool('commit');
    if ($idOrder <= 0) throw new \Exception('idOrder is required.');

    /** @var TrainingOrder */
    $mOrder = $this->getModel(TrainingOrder::class);
    $order = $mOrder->record->find($idOrder);
    if (!$order) throw new \Exception('Order not found.');
    if (empty($order->applicants_xlsx)) throw new \Exception('No applicants file has been uploaded for this order.');

    $filePath = $this->env()->uploadFolder . '/' . $order->applicants_xlsx;

    $importer = $this->getService(\Hubleto\App\Custom\Trainings\ApplicantsImporter::class);
    $result = $commit ? $importer->import($idOrder, $filePath) : $importer->preview($filePath);

    if ($commit) {
      $mOrder->recalculateTotals($idOrder);
    }

    return [ 'status' => 'success', 'commit' => $commit ] + $result;
  }
}
