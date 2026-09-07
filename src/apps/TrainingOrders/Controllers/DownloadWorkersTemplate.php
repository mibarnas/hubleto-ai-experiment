<?php

namespace Hubleto\App\Custom\TrainingOrders\Controllers;

use Hubleto\App\Custom\TrainingOrders\WorkerImportTemplate;

/**
 * Serves the blank .xlsx a company fills in to enrol its workers.
 *
 * Generated from WorkerImportTemplate rather than kept as a checked-in file, so
 * the sheet the client downloads and the columns the importer accepts are by
 * construction the same thing.
 */
class DownloadWorkersTemplate extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  public function render(): string
  {
    $path = tempnam(sys_get_temp_dir(), 'workers-template-') . '.xlsx';

    try {
      WorkerImportTemplate::writeBlankFile($path);

      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Length: ' . filesize($path));
      header('Content-Disposition: attachment; filename="predloha-uchadzacov.xlsx"');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');

      return (string) file_get_contents($path);
    } finally {
      @unlink($path);
    }
  }
}
