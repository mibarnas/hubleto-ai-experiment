<?php

namespace Hubleto\App\Custom\Certificates;

use Hubleto\Framework\Helper;

use Hubleto\App\Custom\Trainings\Models\Applicant;
use Hubleto\App\Custom\Trainings\Models\TrainingDate;
use Hubleto\App\Custom\Trainings\Models\Training;
use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Certificates\Models\Certificate;

use Hubleto\App\Community\Documents\Loader as DocumentsApp;
use Hubleto\App\Community\Documents\Models\Folder;
use Hubleto\App\Community\Documents\Models\Document;
use Hubleto\App\Community\Documents\Models\DocumentVersion;
use Hubleto\App\Community\Documents\Models\File as DocumentFile;
use Hubleto\App\Community\Settings\Models\Company;

/**
 * Generates a certificate for a completed applicant from the training's
 * uploaded .docx template, saves it under upload/certificates/<year>/
 * <training>/<date>/, mirrors Documents\Generator's Document + Version
 * bookkeeping so it shows up in the Documents section, and emails it to
 * the worker.
 */
class CertificateGenerator extends \Hubleto\Erp\Core
{
  /**
   * @return array{idCertificate:int, file:string, unresolvedPlaceholders:string[]}
   */
  public function generate(int $idApplicant, bool $sendEmail = true): array
  {
    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    $applicant = $mApplicant->record->find($idApplicant);
    if (!$applicant) throw new \Exception('Applicant not found.');
    if (!$applicant->is_completed) throw new \Exception('Applicant has not completed the training yet.');

    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $existing = $mCertificate->record->where('id_applicant', $idApplicant)->first();
    if ($existing) {
      return [ 'idCertificate' => $existing->id, 'file' => $existing->file, 'unresolvedPlaceholders' => [] ];
    }

    /** @var TrainingDate */
    $mTrainingDate = $this->getModel(TrainingDate::class);
    $trainingDate = $mTrainingDate->record->find($applicant->id_training_date);
    if (!$trainingDate) throw new \Exception('Training date not found.');

    /** @var Training */
    $mTraining = $this->getModel(Training::class);
    $training = $mTraining->record->find($trainingDate->id_training);
    if (!$training) throw new \Exception('Training not found.');
    if (empty($training->certificate_template)) throw new \Exception('This training has no certificate template uploaded.');

    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    $worker = $mWorker->record->find($applicant->id_worker);
    if (!$worker) throw new \Exception('Worker not found.');

    $company = null;
    if (!empty($training->id_company)) {
      /** @var Company */
      $mCompany = $this->getModel(Company::class);
      $company = $mCompany->record->find($training->id_company);
    }

    $dateValidUntil = $training->retraining_interval_years > 0
      ? date('Y-m-d', strtotime($trainingDate->datetime_start . ' + ' . (int) $training->retraining_interval_years . ' years'))
      : null;

    $certificateNumber = $this->generateCertificateNumber();

    $vars = [
      'date' => date('Y-m-d'),
      'certificate_number' => $certificateNumber,
      'applicant_name' => trim($worker->title_before . ' ' . $worker->first_name . ' ' . $worker->last_name . ' ' . $worker->title_after),
      'worker_first_name' => $worker->first_name,
      'worker_last_name' => $worker->last_name,
      'worker_email' => $worker->email,
      'worker_workplace' => $worker->workplace_name,
      'company_name' => $company->name ?? '',
      'company_ico' => $company->company_id ?? '',
      'company_dic' => $company->tax_id ?? '',
      'company_ic_dph' => $company->vat_id ?? '',
      'training_name' => $training->name,
      'training_number' => $training->training_number,
      'training_date' => date('Y-m-d', strtotime($trainingDate->datetime_start)),
      'lecturer_name' => trim(($trainingDate->LECTURER->first_name ?? '') . ' ' . ($trainingDate->LECTURER->last_name ?? '')),
      'ruvz_name' => $training->ruvz_name,
      'ruvz_certificate_number' => $training->ruvz_certificate_number,
      'ruvz_date_issued' => $training->ruvz_date_issued,
      'valid_until' => $dateValidUntil,
    ];

    $templatePath = $this->env()->uploadFolder . '/' . $training->certificate_template;
    $destinationPath = $this->buildDestinationPath($training, $trainingDate, $worker);
    $relativePath = $this->relativeToUploadFolder($destinationPath);

    $unresolved = (new DocxTemplate($templatePath))->render($vars, $destinationPath);

    $idDocument = $this->createDocumentEntry($idApplicant, $training->name . ' - ' . $worker->first_name . ' ' . $worker->last_name, $relativePath);

    $certificate = $mCertificate->record->recordCreate([
      'id_applicant' => $idApplicant,
      'id_worker' => $applicant->id_worker,
      'id_training' => $training->id,
      'certificate_number' => $certificateNumber,
      'date_created' => date('Y-m-d'),
      'date_valid_until' => $dateValidUntil,
      'ruvz_name' => $training->ruvz_name,
      'ruvz_certificate_number' => $training->ruvz_certificate_number,
      'ruvz_date_issued' => $training->ruvz_date_issued,
      'id_document' => $idDocument,
      'file' => $relativePath,
    ]);

    if ($sendEmail && !empty($worker->email)) {
      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Your training certificate'),
          $this->translate('Please find attached your certificate for') . ' ' . htmlspecialchars($training->name) . '.',
          [ [ 'name' => basename($destinationPath), 'file' => $relativePath ] ]
        );
        $mCertificate->record->find($certificate['id'])->update(['sent_to_applicant_on' => date('Y-m-d H:i:s')]);
      } catch (\Throwable $e) {
        $this->logger()->error('Failed to email certificate: ' . $e->getMessage());
      }
    }

    return [ 'idCertificate' => $certificate['id'], 'file' => $relativePath, 'unresolvedPlaceholders' => $unresolved ];
  }

  private function generateCertificateNumber(): string
  {
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    $year = date('Y');
    $countThisYear = $mCertificate->record->where('certificate_number', 'like', "AC-{$year}-%")->count();
    return sprintf('AC-%s-%04d', $year, $countThisYear + 1);
  }

  private function buildDestinationPath(Training $training, TrainingDate $trainingDate, Worker $worker): string
  {
    $year = date('Y', strtotime($trainingDate->datetime_start));
    $date = date('Y-m-d', strtotime($trainingDate->datetime_start));
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
   * Mirrors Documents\Generator::createPdfDocumentFromTemplate()'s
   * bookkeeping: a Document + DocumentVersion so the certificate shows up
   * in the Documents section, plus a Folder chain + File row so it also
   * appears in the Documents file browser.
   */
  private function createDocumentEntry(int $idApplicant, string $documentName, string $relativePath): int
  {
    /** @var Document */
    $mDocument = $this->getModel(Document::class);
    $document = $mDocument->record->where('model', Applicant::class)->where('record_id', $idApplicant)->first();

    if ($document) {
      $idDocument = $document->id;
    } else {
      $idDocument = $mDocument->record->recordCreate([
        'model' => Applicant::class,
        'record_id' => $idApplicant,
        'name' => $documentName,
      ])['id'];
    }

    /** @var DocumentVersion */
    $mDocumentVersion = $this->getModel(DocumentVersion::class);
    $mDocumentVersion->record->recordCreate([
      'id_document' => $idDocument,
      'name' => basename($relativePath),
      'file' => $relativePath,
    ]);

    $idFolder = $this->getCertificatesFolderId();
    /** @var DocumentFile */
    $mFile = $this->getModel(DocumentFile::class);
    $mFile->record->recordCreate([
      'id_folder' => $idFolder,
      'name' => $documentName,
      'file' => $relativePath,
    ]);

    return (int) $idDocument;
  }

  private function getCertificatesFolderId(): int
  {
    /** @var Folder */
    $mFolder = $this->getModel(Folder::class);
    $rootFolderId = $this->getService(DocumentsApp::class)->getRootFolderId();

    $folder = $mFolder->record->where('id_parent_folder', $rootFolderId)->where('name', 'Certificates')->first();
    if ($folder) return (int) $folder->id;

    return (int) $mFolder->record->recordCreate([
      'id_parent_folder' => $rootFolderId,
      'name' => 'Certificates',
    ])['id'];
  }
}
