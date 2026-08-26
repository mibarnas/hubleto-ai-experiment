<?php

namespace Hubleto\App\Custom\Trainings;

/**
 * Converts .docx to .pdf with headless LibreOffice.
 *
 * PhpWord's own PDF writers (dompdf/tcpdf/mpdf) lose layout, fonts, tables and
 * images, which is not acceptable for an accredited certificate. LibreOffice
 * renders the same file Word would.
 */
class PdfConverter extends \Hubleto\Erp\Core
{
  private const BINARIES = ['/usr/bin/soffice', '/usr/bin/libreoffice', 'soffice', 'libreoffice'];

  public function convert(string $docxPath, ?string $outputDir = null): string
  {
    if (!is_file($docxPath)) throw new \Exception('Source document not found: ' . $docxPath);

    $outputDir = $outputDir ?: dirname($docxPath);
    if (!is_dir($outputDir)) mkdir($outputDir, 0775, true);

    $binary = $this->findBinary();
    if ($binary === null) throw new \Exception('LibreOffice is not available; cannot convert the certificate to PDF.');

    // A private user profile keeps concurrent conversions from fighting over
    // the default one, which otherwise makes soffice exit without converting.
    $profileDir = sys_get_temp_dir() . '/hubleto-soffice-' . getmypid();

    $command = escapeshellcmd($binary)
      . ' --headless --norestore --nolockcheck'
      . ' -env:UserInstallation=file://' . escapeshellarg($profileDir)
      . ' --convert-to pdf --outdir ' . escapeshellarg($outputDir)
      . ' ' . escapeshellarg($docxPath)
      . ' 2>&1';

    exec($command, $output, $exitCode);

    $pdfPath = rtrim($outputDir, '/') . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';

    if ($exitCode !== 0 || !is_file($pdfPath)) {
      throw new \Exception('PDF conversion failed: ' . implode(' ', $output));
    }

    return $pdfPath;
  }

  private function findBinary(): ?string
  {
    foreach (self::BINARIES as $candidate) {
      if (str_starts_with($candidate, '/') && is_executable($candidate)) return $candidate;
      if (!str_starts_with($candidate, '/')) {
        $resolved = trim((string) @shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
        if ($resolved !== '') return $resolved;
      }
    }
    return null;
  }
}
