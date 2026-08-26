<?php

namespace Hubleto\App\Custom\Orders;

use PhpOffice\PhpSpreadsheet\IOFactory;

use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Orders\Models\Order;

/**
 * Reads the .xlsx attached to a group application, upserts each row as a
 * Worker (identified by email, per the spec's "archived based on the
 * attendees' emails"), and attaches an Attendee per row to the order and
 * its training date. Column headers are matched case-insensitively through
 * a small alias map, since the real-world spreadsheet layout is not fixed.
 */
class WorkersImporter extends \Hubleto\Erp\Core
{
  private const COLUMN_ALIASES = [
    'first_name' => ['first name', 'firstname', 'first_name', 'meno'],
    'last_name' => ['last name', 'lastname', 'last_name', 'priezvisko'],
    'email' => ['email', 'e-mail', 'mail'],
    'phone' => ['phone', 'phone number', 'telefon', 'tel'],
    'title_before' => ['title before', 'titul pred'],
    'title_after' => ['title after', 'titul za'],
    'workplace_name' => ['workplace', 'workplace name', 'pracovisko'],
  ];

  public function preview(string $xlsxPath): array
  {
    return $this->process($xlsxPath, null);
  }

  public function import(int $idOrder, string $xlsxPath): array
  {
    return $this->process($xlsxPath, $idOrder);
  }

  /**
   * @param int|null $idOrder When null, rows are parsed and matched but nothing is written.
   */
  private function process(string $xlsxPath, ?int $idOrder): array
  {
    if (!is_file($xlsxPath)) throw new \Exception('Applicants file not found.');

    $spreadsheet = IOFactory::load($xlsxPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false);

    if (empty($rows)) return [ 'matched' => [], 'new' => [], 'invalid' => [], 'unmappedColumns' => [] ];

    $headerRow = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
    $columnMap = $this->mapColumns($headerRow);
    $unmappedColumns = array_values(array_diff_key($headerRow, $columnMap));

    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    /** @var Order */
    $mOrder = $idOrder ? $this->getModel(Order::class)->record->find($idOrder) : null;
    $idSchedule = $mOrder?->id_schedule ?? 0;

    $matched = [];
    $created = [];
    $invalid = [];

    foreach ($rows as $row) {
      $data = [];
      foreach ($columnMap as $colIndex => $field) {
        $data[$field] = trim((string) ($row[$colIndex] ?? ''));
      }

      if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $invalid[] = $data;
        continue;
      }
      $data['email'] = strtolower($data['email']);

      $existingWorker = $mWorker->record->where('email', $data['email'])->first();

      if ($idOrder === null) {
        if ($existingWorker) $matched[] = $data + ['id_worker' => $existingWorker->id];
        else $created[] = $data;
        continue;
      }

      if ($existingWorker) {
        $idWorker = $existingWorker->id;
        $matched[] = $data + ['id_worker' => $idWorker];
      } else {
        $newWorker = $mWorker->record->recordCreate(array_filter([
          'first_name' => $data['first_name'] ?? '',
          'last_name' => $data['last_name'] ?? '',
          'email' => $data['email'],
          'phone' => $data['phone'] ?? '',
          'title_before' => $data['title_before'] ?? '',
          'title_after' => $data['title_after'] ?? '',
          'workplace_name' => $data['workplace_name'] ?? '',
        ]));
        $idWorker = $newWorker['id'];
        $created[] = $data + ['id_worker' => $idWorker];
      }

      $existingApplicant = $mAttendee->record
        ->where('id_schedule', $idSchedule)
        ->where('id_worker', $idWorker)
        ->first();

      if (!$existingApplicant) {
        $mAttendee->record->recordCreate([
          'id_schedule' => $idSchedule,
          'id_worker' => $idWorker,
          'id_order' => $idOrder,
          'date_registered' => date('Y-m-d'),
        ]);
      }
    }

    return [
      'matched' => $matched,
      'new' => $created,
      'invalid' => $invalid,
      'unmappedColumns' => $unmappedColumns,
    ];
  }

  /**
   * @return array<int, string> spreadsheet column index => Worker field name
   */
  private function mapColumns(array $headerRow): array
  {
    $map = [];
    foreach ($headerRow as $index => $header) {
      foreach (self::COLUMN_ALIASES as $field => $aliases) {
        if (in_array($header, $aliases, true)) {
          $map[$index] = $field;
          break;
        }
      }
    }
    return $map;
  }
}
