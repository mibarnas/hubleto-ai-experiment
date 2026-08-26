<?php

namespace Hubleto\App\Custom\Trainings;

use PhpOffice\PhpWord\TemplateProcessor;

use Hubleto\Framework\Helper;

use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Trainings\Models\Schedule;
use Hubleto\App\Custom\Trainings\Models\Training;
use Hubleto\App\Custom\Trainings\Models\Certificate;
use Hubleto\App\Custom\Workers\Models\Worker;

use Hubleto\App\Community\Documents\Loader as DocumentsApp;
use Hubleto\App\Community\Documents\Models\Folder;
use Hubleto\App\Community\Documents\Models\Document;
use Hubleto\App\Community\Documents\Models\DocumentVersion;
use Hubleto\App\Community\Documents\Models\File as DocumentFile;
use Hubleto\App\Community\Settings\Models\Company;

/**
 * Produces an attendee's certificate: merges the training's .docx template with
 * PhpWord, converts it to PDF with LibreOffice, files both under Documents and
 * emails the PDF to the attendee.
 *
 * Placeholders use the client's `<name>` syntax. In the document XML those are
 * escaped, so PhpWord is configured with `&lt;` / `&gt;` as macro delimiters --
 * which also lets its fixBrokenMacros handling repair placeholders Word split
 * across runs.
 */
class CertificateGenerator extends \Hubleto\Erp\Core
{
  /**
   * @param array $overrides Values from the "generate certificate" form.
   * @return array{idCertificate:int, file:string, fileDocx:string, unresolvedPlaceholders:string[]}
   */
  public function generate(int $idAttendee, bool $sendEmail = true, array $overrides = []): array
  {
    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendee = $mAttendee->record->find($idAttendee);
    if (!$attendee) throw new \Exception('Attendee not found.');
    if (!$attendee->is_passed) throw new \Exception('The attendee has not passed the training yet.');

    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);

    if ($attendee->id_certificate > 0) {
      $existing = $mCertificate->record->find($attendee->id_certificate);
      if ($existing) {
        return [
          'idCertificate' => (int) $existing->id,
          'file' => (string) $existing->file,
          'fileDocx' => (string) $existing->file_docx,
          'unresolvedPlaceholders' => [],
        ];
      }
    }

    $schedule = $this->getModel(Schedule::class)->record->find($attendee->id_schedule);
    if (!$schedule) throw new \Exception('Schedule not found.');

    $training = $this->getModel(Training::class)->record->find($schedule->id_training);
    if (!$training) throw new \Exception('Training not found.');
    if (empty($training->template)) throw new \Exception('This training has no certificate template uploaded.');

    $worker = $this->getModel(Worker::class)->record->find($attendee->id_worker);
    if (!$worker) throw new \Exception('Worker not found.');

    $company = empty($training->id_company) ? null
      : $this->getModel(Company::class)->record->find($training->id_company);

    $dateExpiration = $training->interval > 0
      ? date('Y-m-d', strtotime($schedule->date_start . ' + ' . (int) $training->interval . ' years'))
      : null;

    $certificateData = array_merge([
      'internal_number' => $this->generateInternalNumber(),
      'date_internal_validity' => $dateExpiration,
      'external_number' => $training->external_number,
      'date_external_validity' => $dateExpiration,
      'name_validator' => $training->name_validator,
      'date_expiration' => $dateExpiration,
    ], array_filter($overrides, fn($v) => $v !== null && $v !== ''));

    $vars = [
      'date' => date('Y-m-d'),
      'certificate_number' => $certificateData['internal_number'],
      'internal_number' => $certificateData['internal_number'],
      'external_number' => $certificateData['external_number'],
      'name_validator' => $certificateData['name_validator'],
      'valid_until' => $certificateData['date_expiration'],
      'date_expiration' => $certificateData['date_expiration'],
      'applicant_name' => trim($worker->title_before . ' ' . $worker->first_name . ' ' . $worker->last_name . ' ' . $worker->title_after),
      'worker_first_name' => $worker->first_name,
      'worker_last_name' => $worker->last_name,
      'worker_email' => $worker->email,
      'worker_birth_number' => $worker->birth_number,
      'worker_workplace' => $worker->workplace_name,
      'company_name' => $company->name ?? '',
      'company_ico' => $company->company_id ?? '',
      'company_dic' => $company->tax_id ?? '',
      'company_ic_dph' => $company->vat_id ?? '',
      'training_name' => $training->name,
      'training_number' => $training->number,
      'training_date' => date('Y-m-d', strtotime($schedule->date_start)),
      'lecturer_name' => trim(($schedule->LECTURER->first_name ?? '') . ' ' . ($schedule->LECTURER->last_name ?? '')),
      // Legacy names kept so existing templates keep resolving.
      'ruvz_name' => $certificateData['name_validator'],
      'ruvz_certificate_number' => $certificateData['external_number'],
      'ruvz_date_issued' => $training->date_external_issued,
    ];

    $templatePath = $this->env()->uploadFolder . '/' . $training->template;
    $docxPath = $this->buildDestinationPath($training, $schedule, $worker);

    $unresolved = $this->merge($templatePath, $vars, $docxPath);

    $pdfPath = $this->getService(PdfConverter::class)->convert($docxPath);

    $relativeDocx = $this->relativeToUploadFolder($docxPath);
    $relativePdf = $this->relativeToUploadFolder($pdfPath);

    $idDocument = $this->createDocumentEntry(
      $idAttendee,
      $training->name . ' - ' . $worker->first_name . ' ' . $worker->last_name,
      $relativePdf,
      $training,
      $schedule
    );

    $certificate = $mCertificate->record->recordCreate($certificateData + [
      'id_worker' => $attendee->id_worker,
      'id_training' => $training->id,
      'id_document' => $idDocument,
      'file' => $relativePdf,
      'file_docx' => $relativeDocx,
    ]);

    // The ERD keeps the link on the attendee.
    $mAttendee->record->find($idAttendee)->update(['id_certificate' => $certificate['id']]);

    if ($sendEmail && !empty($worker->email)) {
      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Your training certificate'),
          $this->translate('Please find attached your certificate for') . ' ' . htmlspecialchars($training->name) . '.',
          [ [ 'name' => basename($pdfPath), 'file' => $relativePdf ] ]
        );
        $mCertificate->record->find($certificate['id'])->update(['date_sent' => date('Y-m-d H:i:s')]);
      } catch (\Throwable $e) {
        $this->logger()->error('Failed to email certificate: ' . $e->getMessage());
      }
    }

    return [
      'idCertificate' => (int) $certificate['id'],
      'file' => $relativePdf,
      'fileDocx' => $relativeDocx,
      'unresolvedPlaceholders' => $unresolved,
    ];
  }

  /**
   * @return string[] placeholders the template asked for but we cannot supply
   */
  private function merge(string $templatePath, array $vars, string $destinationPath): array
  {
    // Normalise split runs ourselves first -- PhpWord's fixBrokenMacros() cannot
    // handle the escaped `&lt;` delimiters (its generated regex contains \l,
    // which PCRE2 rejects), so without this a placeholder Word broke across runs
    // would never be substituted.
    $normalisedPath = tempnam(sys_get_temp_dir(), 'hbl-tpl-') . '.docx';
    (new DocxTemplate($templatePath))->writeRunMergedCopy($normalisedPath);

    $processor = new TemplateProcessor($normalisedPath);
    $processor->setMacroChars('&lt;', '&gt;');

    $declared = $processor->getVariables();

    foreach ($vars as $name => $value) {
      if (!in_array($name, $declared, true)) continue;
      $processor->setValue($name, htmlspecialchars((string) $value, ENT_XML1));
    }

    $processor->saveAs($destinationPath);
    @unlink($normalisedPath);

    // Anything the template declared that we had no value for is reported
    // rather than silently blanked.
    return array_values(array_diff($declared, array_keys($vars)));
  }

  private function generateInternalNumber(): string
  {
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $year = date('Y');
    $countThisYear = $mCertificate->record->where('internal_number', 'like', "AC-{$year}-%")->count();
    return sprintf('AC-%s-%04d', $year, $countThisYear + 1);
  }

  private function buildDestinationPath(mixed $training, mixed $schedule, mixed $worker): string
  {
    $year = date('Y', strtotime($schedule->date_start));
    $date = date('Y-m-d', strtotime($schedule->date_start));
    $trainingSlug = Helper::str2url($training->name);
    $workerSlug = Helper::str2url($worker->first_name . ' ' . $worker->last_name);

    $dir = $this->env()->uploadFolder . "/certificates/{$year}/{$trainingSlug}/{$date}";
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    return "{$dir}/{$workerSlug}.docx";
  }

  private function relativeToUploadFolder(string $absolutePath): string
  {
    return ltrim(str_replace($this->env()->uploadFolder, '', $absolutePath), '/');
  }

  /**
   * Files the certificate under Documents in a year / training / schedule
   * folder chain, linked to the attendee so it is easy to find.
   */
  private function createDocumentEntry(int $idAttendee, string $documentName, string $relativePath, mixed $training, mixed $schedule): int
  {
    /** @var Document */
    $mDocument = $this->getModel(Document::class);
    $document = $mDocument->record->where('model', Attendee::class)->where('record_id', $idAttendee)->first();

    $idDocument = $document
      ? (int) $document->id
      : (int) $mDocument->record->recordCreate([
          'model' => Attendee::class,
          'record_id' => $idAttendee,
          'name' => $documentName,
        ])['id'];

    $this->getModel(DocumentVersion::class)->record->recordCreate([
      'id_document' => $idDocument,
      'name' => basename($relativePath),
      'file' => $relativePath,
    ]);

    $idFolder = $this->resolveFolderChain([
      'Certificates',
      date('Y', strtotime($schedule->date_start)),
      (string) $training->name,
      date('Y-m-d', strtotime($schedule->date_start)),
    ]);

    $this->getModel(DocumentFile::class)->record->recordCreate([
      'id_folder' => $idFolder,
      'name' => $documentName,
      'file' => $relativePath,
    ]);

    return $idDocument;
  }

  /** @param string[] $path */
  private function resolveFolderChain(array $path): int
  {
    /** @var Folder */
    $mFolder = $this->getModel(Folder::class);
    $idParent = $this->getService(DocumentsApp::class)->getRootFolderId();

    foreach ($path as $name) {
      $folder = $mFolder->record->where('id_parent_folder', $idParent)->where('name', $name)->first();
      $idParent = $folder
        ? (int) $folder->id
        : (int) $mFolder->record->recordCreate(['id_parent_folder' => $idParent, 'name' => $name])['id'];
    }

    return $idParent;
  }
}
