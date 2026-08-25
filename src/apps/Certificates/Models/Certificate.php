<?php

namespace Hubleto\App\Custom\Certificates\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Custom\Trainings\Models\Applicant;
use Hubleto\App\Custom\Trainings\Models\Training;
use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Community\Documents\Models\Document;

class Certificate extends \Hubleto\Erp\Model
{
  public string $table = 'training_certificates';
  public string $recordManagerClass = RecordManagers\Certificate::class;
  public ?string $lookupSqlValue = '{%TABLE%}.certificate_number';
  public ?string $lookupUrlDetail = 'certificates/{%ID%}';

  public array $relations = [
    'APPLICANT' => [ self::BELONGS_TO, Applicant::class, 'id_applicant', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'TRAINING' => [ self::BELONGS_TO, Training::class, 'id_training', 'id' ],
    'DOCUMENT' => [ self::BELONGS_TO, Document::class, 'id_document', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_applicant' => (new Lookup($this, $this->translate('Applicant'), Applicant::class))->setRequired()->setDefaultVisible(),
      'id_worker' => (new Lookup($this, $this->translate('Worker'), Worker::class))->setDefaultVisible(),
      'id_training' => (new Lookup($this, $this->translate('Training'), Training::class))->setDefaultVisible(),
      'certificate_number' => (new Varchar($this, $this->translate('Certificate number')))->setDefaultVisible(),
      'date_created' => (new Date($this, $this->translate('Date of creation')))->setDefaultVisible()->setDefaultValue(date('Y-m-d')),
      'date_valid_until' => (new Date($this, $this->translate('Valid until')))->setDefaultVisible(),
      'ruvz_name' => (new Varchar($this, $this->translate('RÚVZ name'))),
      'ruvz_certificate_number' => (new Varchar($this, $this->translate('RÚVZ certificate number'))),
      'ruvz_date_issued' => (new Date($this, $this->translate('RÚVZ date issued'))),
      'id_document' => (new Lookup($this, $this->translate('Document'), Document::class))->setReadonly()->setDefaultVisible(),
      'file' => (new Varchar($this, $this->translate('Certificate file')))->setReadonly()->setDefaultVisible(),
      'sent_to_applicant_on' => (new DateTime($this, $this->translate('Sent to applicant on')))->setReadonly()->setDefaultVisible(),
    ]);
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'id_applicant' => [
        'type' => 'unique',
        'columns' => [ 'id_applicant' => [ 'order' => 'asc' ] ],
      ],
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
