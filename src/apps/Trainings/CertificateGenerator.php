<?php

namespace Hubleto\App\Custom\Trainings;

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
use Hubleto\App\Community\Customers\Models\Customer;

/**
 * Produces an attendee's certificate: merges the training's .docx template,
 * converts it to PDF with LibreOffice, files both under Documents and emails
 * the PDF to the attendee.
 *
 * Placeholders use the client's `<< value >>` syntax and are substituted by
 * DocxTemplate. PhpWord's TemplateProcessor is deliberately not used: in the
 * document XML the delimiters appear XML-escaped, and the regex PhpWord builds
 * from `&lt;` contains `\l`, which PCRE2 rejects -- so its macro handling could
 * never match these templates in the first place.
 */
class CertificateGenerator extends \Hubleto\Erp\Core
{
  // Plain services get no context of their own, so its own messages would
  // never be translated without this.
  public string $translationContext = 'hubleto-app-custom-trainings-loader';
  public string $translationContextInner = 'CertificateGenerator';

  /**
   * @param array $overrides Values from the "generate certificate" form.
   * @return array{
   *   idCertificate: int,
   *   file: string,
   *   fileDocx: string,
   *   unresolvedPlaceholders: string[],
   *   unknownPlaceholders: string[]
   * }
   */
  public function generate(int $idAttendee, bool $sendEmail = true, array $overrides = []): array
  {
    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendee = $mAttendee->record->find($idAttendee);
    if (!$attendee) throw new \Exception($this->translate('Attendee not found.'));
    if (!$attendee->is_passed) throw new \Exception($this->translate('The attendee has not passed the training yet.'));

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
          'unknownPlaceholders' => [],
        ];
      }
    }

    $schedule = $this->getModel(Schedule::class)->record->find($attendee->id_schedule);
    if (!$schedule) throw new \Exception($this->translate('Schedule not found.'));

    $training = $this->getModel(Training::class)->record->find($schedule->id_training);
    if (!$training) throw new \Exception($this->translate('Training not found.'));
    if (empty($training->template)) throw new \Exception($this->translate('This training has no certificate template uploaded.'));

    $worker = $this->getModel(Worker::class)->record->find($attendee->id_worker);
    if (!$worker) throw new \Exception($this->translate('Worker not found.'));

    $templatePath = $this->env()->uploadFolder . '/' . $training->template;
    if (!is_file($templatePath)) {
      throw new \Exception($this->translate('The certificate template of this training is missing on the server.'));
    }

    $company = empty($training->id_company) ? null
      : $this->getModel(Company::class)->record->find($training->id_company);

    $employer = empty($worker->id_customer) ? null
      : $this->getModel(Customer::class)->record->find($worker->id_customer);

    $dateExpiration = $training->interval > 0
      ? date('Y-m-d', strtotime($schedule->date_start . ' + ' . (int) $training->interval . ' years'))
      : null;

    // Accreditation is a property of the issued certificate, not of the
    // training, so it only ever comes from the generate form.
    $certificateData = array_merge([
      'internal_number' => $this->generateInternalNumber(),
      'date_internal_validity' => $dateExpiration,
      'date_external_validity' => $dateExpiration,
      'date_expiration' => $dateExpiration,
    ], array_filter($overrides, fn($v) => $v !== null && $v !== ''));

    $vars = $this->buildVariables($certificateData, $worker, $employer, $training, $schedule, $company);

    $docxPath = $this->buildDestinationPath($training, $schedule, $worker);
    $result = (new DocxTemplate($templatePath))->render($vars, $docxPath);

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
          $this->translate('Please find attached your certificate for') . ' ' . htmlspecialchars((string) $training->name) . '.',
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
      'unresolvedPlaceholders' => $result['unresolved'],
      'unknownPlaceholders' => $result['unknown'],
    ];
  }

  /**
   * The values a template can ask for, keyed by the canonical names in
   * TemplateVariables.
   *
   * @return array<string, string>
   */
  private function buildVariables(
    array $certificateData,
    mixed $worker,
    mixed $employer,
    mixed $training,
    mixed $schedule,
    mixed $company
  ): array
  {
    $fullName = trim(implode(' ', array_filter([
      $worker->title_before, $worker->first_name, $worker->last_name, $worker->title_after,
    ])));

    return [
      // attendee
      'applicant_name' => $fullName,
      'first_name' => (string) $worker->first_name,
      'last_name' => (string) $worker->last_name,
      'title_before' => (string) $worker->title_before,
      'title_after' => (string) $worker->title_after,
      'birth_number' => (string) $worker->birth_number,
      'email' => (string) $worker->email,
      'address' => (string) $worker->address,
      'city' => (string) $worker->city,
      'zip' => (string) $worker->zip,
      'workplace' => (string) $worker->workplace_name,
      'employer' => (string) ($employer->name ?? ''),

      // training
      'training_name' => (string) $training->name,
      'training_number' => (string) $training->number,
      'training_date' => $this->formatDate($schedule->date_start),
      'training_date_end' => $this->formatDate($schedule->date_end),
      'training_interval' => (string) ($training->interval ?? ''),

      // certificate
      'certificate_number' => (string) ($certificateData['internal_number'] ?? ''),
      'external_number' => (string) ($certificateData['external_number'] ?? ''),
      'name_validator' => (string) ($certificateData['name_validator'] ?? ''),
      'date_issued' => date('d.m.Y'),
      'valid_until' => $this->formatDate($certificateData['date_expiration'] ?? null),

      // provider
      'company_name' => (string) ($company->name ?? ''),
      'company_id' => (string) ($company->company_id ?? ''),
      'tax_id' => (string) ($company->tax_id ?? ''),
      'vat_id' => (string) ($company->vat_id ?? ''),
      'company_address' => trim(implode(', ', array_filter([
        $company->street_1 ?? '',
        trim(($company->zip ?? '') . ' ' . ($company->city ?? '')),
      ]))),
    ];
  }

  private function formatDate(?string $value): string
  {
    if (empty($value)) return '';
    $timestamp = strtotime($value);
    return $timestamp === false ? '' : date('d.m.Y', $timestamp);
  }

  private function generateInternalNumber(): string
  {
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $year = date('Y');

    // Counting rows would reuse a number as soon as one is deleted, so the
    // sequence continues from the highest number actually issued this year.
    $latest = $mCertificate->record
      ->where('internal_number', 'like', "AC-{$year}-%")
      ->orderByRaw('cast(substring(`internal_number`, 9) as unsigned) desc')
      ->value('internal_number')
    ;

    $next = $latest === null ? 1 : ((int) substr((string) $latest, 8)) + 1;

    return sprintf('AC-%s-%04d', $year, $next);
  }

  private function buildDestinationPath(mixed $training, mixed $schedule, mixed $worker): string
  {
    $year = date('Y', strtotime($schedule->date_start));
    $date = date('Y-m-d', strtotime($schedule->date_start));
    $trainingSlug = Helper::str2url($training->name);
    $workerSlug = Helper::str2url($worker->first_name . ' ' . $worker->last_name);

    $dir = $this->env()->uploadFolder . "/certificates/{$year}/{$trainingSlug}/{$date}";
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // Two workers can share a name, and a certificate must never overwrite
    // somebody else's, so the worker id is part of the file name.
    return "{$dir}/{$workerSlug}-{$worker->id}.docx";
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
