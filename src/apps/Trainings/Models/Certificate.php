<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Community\Documents\Models\Document;

class Certificate extends \Hubleto\Erp\Model
{
  public string $table = 'certificates';
  public string $recordManagerClass = RecordManagers\Certificate::class;
  public ?string $lookupSqlValue = '{%TABLE%}.internal_number';
  public ?string $lookupUrlDetail = 'trainings/certificates/{%ID%}';

  public array $relations = [
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'TRAINING' => [ self::BELONGS_TO, Training::class, 'id_training', 'id' ],
    'DOCUMENT' => [ self::BELONGS_TO, Document::class, 'id_document', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      // Denormalised from the attendee so the worker card, retraining calculator
      // and statistics do not have to join back through attendees every time.
      'id_worker' => (new Lookup($this, $this->translate('Worker'), Worker::class))->setDefaultVisible(),
      'id_training' => (new Lookup($this, $this->translate('Training'), Training::class))->setDefaultVisible(),
      'internal_number' => (new Varchar($this, $this->translate('Internal certificate number')))->setDefaultVisible(),
      'date_internal_validity' => (new Date($this, $this->translate('Internal validity')))->setDefaultVisible(),
      'external_number' => (new Varchar($this, $this->translate('External certificate number'))),
      'date_external_validity' => (new Date($this, $this->translate('External validity'))),
      'name_validator' => (new Varchar($this, $this->translate('Accreditation authority'))),
      'date_expiration' => (new Date($this, $this->translate('Expires on')))->setDefaultVisible(),
      'file' => (new Varchar($this, $this->translate('Certificate (PDF)')))->setReadonly()->setDefaultVisible(),
      'file_docx' => (new Varchar($this, $this->translate('Certificate (source .docx)')))->setReadonly(),
      'id_document' => (new Lookup($this, $this->translate('Document'), Document::class))->setReadonly()->setDefaultVisible(),
      'date_sent' => (new DateTime($this, $this->translate('Sent to attendee on')))->setReadonly()->setDefaultVisible(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['title'] = $this->translate('Certificates');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);
    $this->recalculateWorkerRetraining($savedRecord);
    return $savedRecord;
  }

  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);
    $this->recalculateWorkerRetraining($savedRecord);
    return $savedRecord;
  }

  private function recalculateWorkerRetraining(array $record): void
  {
    if (empty($record['id_worker'])) return;
    $this->getService(\Hubleto\App\Custom\Workers\RetrainingCalculator::class)
      ->recalculate((int) $record['id_worker']);
  }

}
