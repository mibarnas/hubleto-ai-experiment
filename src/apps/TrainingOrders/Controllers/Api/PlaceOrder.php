<?php

namespace Hubleto\App\Custom\TrainingOrders\Controllers\Api;

use Hubleto\App\Custom\TrainingOrders\Models\Order;
use Hubleto\App\Custom\TrainingOrders\WorkersImporter;
use Hubleto\App\Custom\Trainings\Models\Schedule;
use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Community\Customers\Models\Customer;

/**
 * Creates a training order from the public website.
 *
 * Called through the community Api app's gateway, which authenticates the
 * request and records its usage:
 *
 *   POST api/call
 *   {
 *     "key": "<api key>",
 *     "app": "Hubleto\\App\\Custom\\TrainingOrders",
 *     "controller": "PlaceOrder",
 *     "vars": {
 *       "idSchedule": 12,
 *       "orderType": "company",              // or "private"
 *       "company": { "name": "...", "company_id": "...", "email": "..." },
 *       "workers": [ { "first_name": "...", "last_name": "...", "email": "..." }, ... ],
 *       "note": "..."
 *     }
 *   }
 *
 * A private order carries exactly one entry in `workers`; a company order
 * carries as many as it likes. Either way the workers go through the same
 * importer the manual and .xlsx paths use, so a person who has attended before
 * is matched on their email instead of being duplicated.
 */
class PlaceOrder extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $vars = $this->router()->getUrlParams();

    $idSchedule = (int) ($vars['idSchedule'] ?? 0);
    if ($idSchedule <= 0) throw new \Exception('idSchedule is required.');

    $schedule = $this->getModel(Schedule::class)->record->find($idSchedule);
    if (!$schedule) throw new \Exception('Unknown training date.');

    $workers = $vars['workers'] ?? [];
    if (is_string($workers)) $workers = json_decode($workers, true);
    if (!is_array($workers) || empty($workers)) throw new \Exception('At least one worker is required.');

    $isCompany = strtolower((string) ($vars['orderType'] ?? 'private')) === 'company';

    if (!$isCompany && count($workers) > 1) {
      throw new \Exception('A private order can only contain one person.');
    }

    /** @var Order */
    $mOrder = $this->getModel(Order::class);

    $record = [
      'identifier' => $mOrder->generateIdentifier(),
      'order_type' => $isCompany ? Order::TYPE_COMPANY : Order::TYPE_PRIVATE,
      'id_schedule' => $idSchedule,
      'date_ordered' => date('Y-m-d'),
      'note' => (string) ($vars['note'] ?? ''),
    ];

    if ($isCompany) {
      $record['id_customer'] = $this->resolveCustomer((array) ($vars['company'] ?? []));
    }

    $order = $mOrder->record->recordCreate($record);
    $idOrder = (int) $order['id'];

    $result = $this->getService(WorkersImporter::class)->importRows($workers, $idOrder);

    // A private order is the individual's own order, so the first (and only)
    // worker is also its orderer.
    if (!$isCompany) {
      $idWorker = $result['matched'][0]['id_worker'] ?? $result['new'][0]['id_worker'] ?? 0;
      if ($idWorker > 0) $mOrder->record->find($idOrder)->update(['id_worker' => $idWorker]);
    }

    return [
      'status' => 'success',
      'idOrder' => $idOrder,
      'identifier' => $order['identifier'],
      'enrolled' => count($result['matched']) + count($result['new']),
      'rejected' => $result['invalid'],
      'pricePerPerson' => $mOrder->getPricePerPerson($idOrder),
    ];
  }

  /**
   * Matches the ordering company on its registration ID, falling back to its
   * name, and creates it when the website sends one we have never seen.
   */
  private function resolveCustomer(array $company): ?int
  {
    $name = trim((string) ($company['name'] ?? ''));
    $companyId = trim((string) ($company['company_id'] ?? ''));

    if ($name === '' && $companyId === '') {
      throw new \Exception('A company order needs at least the company name.');
    }

    /** @var Customer */
    $mCustomer = $this->getModel(Customer::class);

    $existing = null;
    if ($companyId !== '') $existing = $mCustomer->record->where('company_id', $companyId)->first();
    if (!$existing && $name !== '') $existing = $mCustomer->record->where('name', $name)->first();

    if ($existing) return (int) $existing->id;

    return (int) $mCustomer->record->recordCreate(array_filter([
      'name' => $name !== '' ? $name : $companyId,
      'company_id' => $companyId,
      'vat_id' => trim((string) ($company['vat_id'] ?? '')),
      'tax_id' => trim((string) ($company['tax_id'] ?? '')),
      'street_line_1' => trim((string) ($company['address'] ?? '')),
      'city' => trim((string) ($company['city'] ?? '')),
      'postal_code' => trim((string) ($company['zip'] ?? '')),
      'date_created' => date('Y-m-d'),
      'is_active' => 1,
    ], fn($v) => $v !== '' && $v !== null))['id'];
  }
}
