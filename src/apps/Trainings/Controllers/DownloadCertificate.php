<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

use Hubleto\App\Custom\Trainings\Models\Certificate;

/**
 * Streams a generated certificate. upload/ is blocked from direct HTTP access
 * by upload/.htaccess, so the file is served through an authenticated route.
 */
class DownloadCertificate extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  public function render(): string
  {
    $idCertificate = $this->router()->urlParamAsInteger('id');
    $wantsSource = $this->router()->urlParamAsBool('docx');

    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $certificate = $idCertificate > 0 ? $mCertificate->record->find($idCertificate) : null;

    $relative = $certificate ? ($wantsSource ? $certificate->file_docx : $certificate->file) : null;
    if (!$certificate || empty($relative)) {
      http_response_code(404);
      return '';
    }

    $uploadFolder = realpath($this->env()->uploadFolder);
    $filePath = realpath($this->env()->uploadFolder . '/' . $relative);

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
