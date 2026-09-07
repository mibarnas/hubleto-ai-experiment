<?php

namespace Hubleto\App\Custom\TrainingOrders;

use PhpOffice\PhpSpreadsheet\IOFactory;

use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\TrainingOrders\Models\Order;

/**
 * Enrols several workers on an order at once.
 *
 * Rows come either from the .xlsx a company attaches to its order (laid out
 * per WorkerImportTemplate) or from the website API. Both paths converge on
 * importRows(), so a worker enrolled through the API is created and matched
 * exactly like one imported from a spreadsheet.
 *
 * A worker is identified by email, per the spec's "archived based on the
 * attendees' emails": a known address updates the existing worker, an unknown
 * one creates a new record.
 */
class WorkersImporter extends \Hubleto\Erp\Core
{
  public string $translationContext = 'hubleto-app-custom-trainingorders-loader';
  public string $translationContextInner = 'WorkersImporter';

  /** Previews an .xlsx without writing anything. */
  public function preview(string $xlsxPath): array
  {
    return $this->importRows($this->readRows($xlsxPath), null) + $this->describeFile($xlsxPath);
  }

  /** Imports an .xlsx into an order. */
  public function import(int $idOrder, string $xlsxPath): array
  {
    return $this->importRows($this->readRows($xlsxPath), $idOrder) + $this->describeFile($xlsxPath);
  }

  /**
   * The one place rows become workers and attendees, whether they arrived from
   * a spreadsheet, from the order form or from the website API.
   *
   * @param array<int, array<string, string>> $rows keyed by WorkerImportTemplate fields
   * @param int|null $idOrder When null, rows are validated and matched but nothing is written.
   * @return array{matched: array, new: array, invalid: array}
   */
  public function importRows(array $rows, ?int $idOrder): array
  {
    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);

    $idSchedule = 0;
    if ($idOrder !== null) {
      $order = $this->getModel(Order::class)->record->find($idOrder);
      if (!$order) throw new \Exception($this->translate('Order not found.'));
      $idSchedule = (int) $order->id_schedule;
      if ($idSchedule <= 0) {
        throw new \Exception($this->translate('The order has no training date selected, so its workers cannot be enrolled.'));
      }
    }

    $matched = [];
    $created = [];
    $invalid = [];

    foreach ($rows as $row) {
      $data = $this->sanitizeRow($row);
      $problems = $this->validateRow($data);

      if (!empty($problems)) {
        $invalid[] = $data + ['problems' => $problems];
        continue;
      }

      $existingWorker = $mWorker->record->where('email', $data['email'])->first();

      if ($idOrder === null) {
        if ($existingWorker) $matched[] = $data + ['id_worker' => (int) $existingWorker->id];
        else $created[] = $data;
        continue;
      }

      if ($existingWorker) {
        $idWorker = (int) $existingWorker->id;
        // The import is also how the office receives corrected details, so
        // values the file actually carries are written back.
        $update = array_filter($data, fn($v, $k) => $v !== '' && $k !== 'email', ARRAY_FILTER_USE_BOTH);
        if (!empty($update)) $mWorker->record->find($idWorker)->update($update);
        $matched[] = $data + ['id_worker' => $idWorker];
      } else {
        $newWorker = $mWorker->record->recordCreate(array_filter($data, fn($v) => $v !== ''));
        $idWorker = (int) $newWorker['id'];
        $created[] = $data + ['id_worker' => $idWorker];
      }

      $existingAttendee = $mAttendee->record
        ->where('id_schedule', $idSchedule)
        ->where('id_worker', $idWorker)
        ->first()
      ;

      if ($existingAttendee) {
        // Already registered for this date -- attach them to this order rather
        // than failing on the (id_schedule, id_worker) unique index.
        if ((int) $existingAttendee->id_order !== $idOrder) {
          $mAttendee->record->find($existingAttendee->id)->update(['id_order' => $idOrder]);
        }
      } else {
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
    ];
  }

  /**
   * @return array<int, array<string, string>> rows keyed by template field
   */
  public function readRows(string $xlsxPath): array
  {
    if (!is_file($xlsxPath)) throw new \Exception($this->translate('The workers file was not found.'));

    $sheet = IOFactory::load($xlsxPath)->getActiveSheet();
    $sheetRows = $sheet->toArray(null, true, true, false);

    if (empty($sheetRows)) return [];

    $columnMap = $this->mapColumns((array) array_shift($sheetRows));
    if (empty($columnMap)) {
      throw new \Exception($this->translate('None of the columns in the file match the import template. Download the template and fill it in.'));
    }

    $rows = [];
    foreach ($sheetRows as $sheetRow) {
      $row = [];
      foreach ($columnMap as $index => $field) {
        $row[$field] = trim((string) ($sheetRow[$index] ?? ''));
      }
      // Excel happily hands back hundreds of blank trailing rows.
      if (count(array_filter($row, fn($v) => $v !== '')) === 0) continue;
      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Which template columns the file provides, and which of its columns are not
   * part of the template -- reported so a mislabelled header is visible instead
   * of silently dropping a column.
   *
   * @return array{recognisedColumns: string[], unmappedColumns: string[], missingRequiredColumns: string[]}
   */
  public function describeFile(string $xlsxPath): array
  {
    $sheet = IOFactory::load($xlsxPath)->getActiveSheet();
    $sheetRows = $sheet->toArray(null, true, true, false);
    $headerRow = (array) ($sheetRows[0] ?? []);

    $recognised = [];
    $unmapped = [];

    foreach ($headerRow as $header) {
      $header = trim((string) $header);
      if ($header === '') continue;

      $field = WorkerImportTemplate::resolveHeader($header);
      if ($field === null) $unmapped[] = $header;
      else $recognised[] = $field;
    }

    return [
      'recognisedColumns' => $recognised,
      'unmappedColumns' => $unmapped,
      'missingRequiredColumns' => array_values(array_diff(WorkerImportTemplate::requiredFields(), $recognised)),
    ];
  }

  /**
   * @return array<int, string> spreadsheet column index => template field
   */
  private function mapColumns(array $headerRow): array
  {
    $map = [];
    foreach ($headerRow as $index => $header) {
      $field = WorkerImportTemplate::resolveHeader((string) $header);
      if ($field !== null) $map[$index] = $field;
    }
    return $map;
  }

  /** @return array<string, string> only known fields, trimmed */
  private function sanitizeRow(array $row): array
  {
    $data = [];
    foreach (WorkerImportTemplate::fields() as $field) {
      $data[$field] = trim((string) ($row[$field] ?? ''));
    }
    $data['email'] = strtolower($data['email']);
    return $data;
  }

  /** @return string[] human-readable reasons the row cannot be imported */
  private function validateRow(array $data): array
  {
    $problems = [];

    foreach (WorkerImportTemplate::requiredFields() as $field) {
      if ($data[$field] === '') {
        $problems[] = $this->translate('Missing value') . ': ' . WorkerImportTemplate::COLUMNS[$field]['label'];
      }
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $problems[] = $this->translate('Invalid email address');
    }

    return $problems;
  }
}
