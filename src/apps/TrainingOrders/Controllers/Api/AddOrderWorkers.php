<?php

namespace Hubleto\App\Custom\TrainingOrders\Controllers\Api;

use Hubleto\App\Custom\TrainingOrders\WorkersImporter;

/**
 * Enrols several workers on an order from a list of rows rather than a file.
 *
 * This is the manual counterpart of the .xlsx import: the order form pastes or
 * types the workers in, and they go through exactly the same matching, updating
 * and enrolling as an imported spreadsheet.
 */
class AddOrderWorkers extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idOrder = $this->router()->urlParamAsInteger('idOrder');
    if ($idOrder <= 0) throw new \Exception($this->translate('idOrder is required.'));

    // Posted as JSON from the order form, but a caller may also send it as a
    // JSON-encoded string, so both shapes are accepted.
    $workers = $this->router()->getUrlParams()['workers'] ?? null;
    if (is_string($workers)) $workers = json_decode($workers, true);
    if (!is_array($workers) || empty($workers)) {
      throw new \Exception($this->translate('No workers were provided.'));
    }

    $commit = $this->router()->urlParamAsBool('commit');

    $result = $this->getService(WorkersImporter::class)->importRows($workers, $commit ? $idOrder : null);

    return [ 'status' => 'success', 'commit' => $commit ] + $result;
  }
}
