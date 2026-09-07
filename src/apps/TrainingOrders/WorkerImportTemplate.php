<?php

namespace Hubleto\App\Custom\TrainingOrders;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The spreadsheet layout ("predloha") a bulk worker import has to follow.
 *
 * One definition drives three things, so they cannot drift apart:
 *   - the blank .xlsx the client downloads and fills in,
 *   - the header matching done when a filled file is imported,
 *   - the field names accepted by the API when workers arrive from the website.
 *
 * Headers are matched case-insensitively and accent-insensitively, and each
 * column accepts a few spellings, because a file that has been round-tripped
 * through Excel rarely comes back with the exact header it left with.
 */
class WorkerImportTemplate
{
  /**
   * field => [label (the header written into the blank template), required,
   *           aliases (additional accepted headers, already normalized)]
   *
   * @var array<string, array{label: string, required: bool, aliases: string[]}>
   */
  const COLUMNS = [
    'title_before' => [
      'label' => 'Titul pred menom',
      'required' => false,
      'aliases' => ['title_before', 'titul_pred'],
    ],
    'first_name' => [
      'label' => 'Meno',
      'required' => true,
      'aliases' => ['first_name', 'firstname', 'krstne_meno'],
    ],
    'last_name' => [
      'label' => 'Priezvisko',
      'required' => true,
      'aliases' => ['last_name', 'lastname', 'surname'],
    ],
    'title_after' => [
      'label' => 'Titul za menom',
      'required' => false,
      'aliases' => ['title_after', 'titul_za'],
    ],
    'email' => [
      'label' => 'E-mail',
      'required' => true,
      'aliases' => ['email', 'mail', 'emailova_adresa'],
    ],
    'phone' => [
      'label' => 'Telefon',
      'required' => false,
      'aliases' => ['phone', 'tel', 'telefonne_cislo', 'mobil'],
    ],
    'birth_number' => [
      'label' => 'Rodne cislo',
      'required' => false,
      'aliases' => ['birth_number', 'rc'],
    ],
    'address' => [
      'label' => 'Ulica',
      'required' => false,
      'aliases' => ['address', 'adresa', 'bydlisko'],
    ],
    'city' => [
      'label' => 'Mesto',
      'required' => false,
      'aliases' => ['city', 'obec'],
    ],
    'zip' => [
      'label' => 'PSC',
      'required' => false,
      'aliases' => ['zip', 'postal_code'],
    ],
    'workplace_name' => [
      'label' => 'Pracovisko',
      'required' => false,
      'aliases' => ['workplace_name', 'workplace', 'prevadzka'],
    ],
    'workplace_address' => [
      'label' => 'Ulica pracoviska',
      'required' => false,
      'aliases' => ['workplace_address', 'adresa_pracoviska'],
    ],
    'workplace_city' => [
      'label' => 'Mesto pracoviska',
      'required' => false,
      'aliases' => ['workplace_city', 'mesto_pracoviska'],
    ],
    'workplace_zip' => [
      'label' => 'PSC pracoviska',
      'required' => false,
      'aliases' => ['workplace_zip', 'psc_pracoviska'],
    ],
  ];

  /** @return string[] */
  public static function fields(): array
  {
    return array_keys(self::COLUMNS);
  }

  /** @return string[] fields a row is rejected without */
  public static function requiredFields(): array
  {
    return array_keys(array_filter(self::COLUMNS, fn($c) => $c['required']));
  }

  /** @return string[] the header row of the blank template, in order */
  public static function headerRow(): array
  {
    return array_map(fn($c) => $c['label'], array_values(self::COLUMNS));
  }

  /**
   * Resolves a spreadsheet header to the field it fills, or null when the
   * column is not part of the template.
   */
  public static function resolveHeader(string $header): ?string
  {
    $normalized = self::normalize($header);
    if ($normalized === '') return null;

    foreach (self::COLUMNS as $field => $definition) {
      if ($normalized === self::normalize($definition['label'])) return $field;
      if (in_array($normalized, $definition['aliases'], true)) return $field;
    }

    return null;
  }

  /** `E-mail`, `E mail` and `email` are the same column. */
  public static function normalize(string $value): string
  {
    $value = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value));
    $value = strtolower($value);
    $value = (string) preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
  }

  /** Writes the blank template the client fills in, and returns its path. */
  public static function writeBlankFile(string $path): string
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Uchadzaci');

    $column = 1;
    foreach (self::COLUMNS as $definition) {
      $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
      $sheet->getCell($letter . '1')->setValue($definition['label'] . ($definition['required'] ? ' *' : ''));
      $sheet->getStyle($letter . '1')->getFont()->setBold(true);
      $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
      $column++;
    }

    $sheet->freezePane('A2');

    (new Xlsx($spreadsheet))->save($path);

    return $path;
  }
}
