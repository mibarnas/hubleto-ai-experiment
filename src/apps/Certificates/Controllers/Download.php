<?php

namespace Hubleto\App\Custom\Certificates\Controllers;

use Hubleto\App\Custom\Certificates\Models\Certificate;

/**
 * Streams a generated certificate to the browser.
 *
 * Generated certificates live under upload/, which is blocked from direct HTTP
 * access by upload/.htaccess ("Require all denied"), so linking straight at
 * uploadUrl always yields 403. Serving the file through an authenticated
 * controller is the same approach Documents\Controllers\DownloadFile takes.
 */
class Download extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  public function render(): string
  {
    $idCertificate = $this->router()->urlParamAsInteger('id');

    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $certificate = $idCertificate > 0 ? $mCertificate->record->find($idCertificate) : null;

    if (!$certificate || empty($certificate->file)) {
      http_response_code(404);
      return '';
    }

    $uploadFolder = realpath($this->env()->uploadFolder);
    $filePath = realpath($this->env()->uploadFolder . '/' . $certificate->file);

    // Keep a crafted `file` value from escaping the upload folder.
    if (
      $uploadFolder === false
      || $filePath === false
      || !str_starts_with($filePath, $uploadFolder . DIRECTORY_SEPARATOR)
      || !is_file($filePath)
    ) {
      http_response_code(404);
      return '';
    }

    header('Content-Type: ' . (mime_content_type($filePath) ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');

    return file_get_contents($filePath);
  }
}
