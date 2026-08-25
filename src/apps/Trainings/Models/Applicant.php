<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Custom\Workers\Models\Worker;

class Applicant extends \Hubleto\Erp\Model
{
  const EDUCATION_BASIC = 1;
  const EDUCATION_SECONDARY = 2;
  const EDUCATION_SECONDARY_WITH_MATURITY = 3;
  const EDUCATION_TERTIARY = 4;

  const EDUCATION_VALUES = [
    self::EDUCATION_BASIC => 'Basic',
    self::EDUCATION_SECONDARY => 'Secondary (without maturity exam)',
    self::EDUCATION_SECONDARY_WITH_MATURITY => 'Secondary (with maturity exam)',
    self::EDUCATION_TERTIARY => 'Tertiary',
  ];

  const FINANCING_SELF = 1;
  const FINANCING_EMPLOYER = 2;
  const FINANCING_OTHER = 3;

  const FINANCING_VALUES = [
    self::FINANCING_SELF => 'Self-financed',
    self::FINANCING_EMPLOYER => 'Financed by employer',
    self::FINANCING_OTHER => 'Other',
  ];

  public string $table = 'training_applicants';
  public string $recordManagerClass = RecordManagers\Applicant::class;
  public ?string $lookupSqlValue = 'concat("Applicant #", {%TABLE%}.id)';
  public ?string $lookupUrlDetail = 'trainings/applicants/{%ID%}';

  public array $relations = [
    'TRAINING_DATE' => [ self::BELONGS_TO, TrainingDate::class, 'id_training_date', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'ORDER' => [ self::BELONGS_TO, TrainingOrder::class, 'id_order', 'id' ],
    'QUESTIONNAIRE' => [ self::HAS_ONE, QuestionnaireAnswer::class, 'id_applicant', 'id' ],
    'CERTIFICATE' => [ self::HAS_ONE, \Hubleto\App\Custom\Certificates\Models\Certificate::class, 'id_applicant', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_training_date' => (new Lookup($this, $this->translate('Training date'), TrainingDate::class))->setRequired()->setDefaultVisible()
        ->setDefaultValue($this->router()->urlParamAsInteger('idTrainingDate')),
      'id_worker' => (new Lookup($this, $this->translate('Worker'), Worker::class))->setRequired()->setDefaultVisible(),
      'id_order' => (new Lookup($this, $this->translate('Order'), TrainingOrder::class))
        ->setDefaultValue($this->router()->urlParamAsInteger('idOrder')),
      'date_registered' => (new Date($this, $this->translate('Date registered')))->setDefaultValue(date('Y-m-d')),
      'is_completed' => (new Boolean($this, $this->translate('Completed')))->setDefaultVisible(),
      'previous_certificate_file' => (new File($this, $this->translate('Previous certificate')))->setFolderPath('previous-certificates'),
      'questionnaire_token' => (new Varchar($this, $this->translate('Questionnaire token')))->setReadonly()->setDefaultHidden(),
      'questionnaire_sent_on' => (new DateTime($this, $this->translate('Questionnaire sent on')))->setReadonly(),
      'questionnaire_filled_on' => (new DateTime($this, $this->translate('Questionnaire filled on')))->setReadonly()->setDefaultVisible(),
      'catalog_token' => (new Varchar($this, $this->translate('Catalog sheet token')))->setReadonly()->setDefaultHidden(),
      'catalog_filled_on' => (new DateTime($this, $this->translate('Catalog sheet filled on')))->setReadonly(),
      'education_level' => (new Integer($this, $this->translate('Highest education')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::EDUCATION_VALUES)),
      'financing_type' => (new Integer($this, $this->translate('Financing')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::FINANCING_VALUES)),
      'meeting_link_sent_on' => (new DateTime($this, $this->translate('Meeting link sent on')))->setReadonly(),
    ]);
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'questionnaire_token' => [
        'type' => 'unique',
        'columns' => [ 'questionnaire_token' => [ 'order' => 'asc' ] ],
      ],
      'catalog_token' => [
        'type' => 'unique',
        'columns' => [ 'catalog_token' => [ 'order' => 'asc' ] ],
      ],
      'id_training_date__id_worker' => [
        'type' => 'unique',
        'columns' => [
          'id_training_date' => [ 'order' => 'asc' ],
          'id_worker' => [ 'order' => 'asc' ],
        ],
      ],
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Applicant');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    if (empty($record['questionnaire_token'])) $record['questionnaire_token'] = bin2hex(random_bytes(16));
    if (empty($record['catalog_token'])) $record['catalog_token'] = bin2hex(random_bytes(16));
    return $record;
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);
    $this->recalculateOrderTotals($savedRecord);
    return $savedRecord;
  }

  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);
    $this->recalculateOrderTotals($originalRecord);
    $this->recalculateOrderTotals($savedRecord);
    return $savedRecord;
  }

  private int $idOrderBeforeDelete = 0;

  public function onBeforeDelete(int $id): int
  {
    $id = parent::onBeforeDelete($id);
    $existing = $this->record->find($id);
    $this->idOrderBeforeDelete = $existing ? (int) $existing->id_order : 0;
    return $id;
  }

  public function onAfterDelete(int $id): int
  {
    $id = parent::onAfterDelete($id);
    if ($this->idOrderBeforeDelete > 0) {
      $this->getModel(TrainingOrder::class)->recalculateTotals($this->idOrderBeforeDelete);
    }
    return $id;
  }

  private function recalculateOrderTotals(array $record): void
  {
    if (empty($record['id_order'])) return;
    $this->getModel(TrainingOrder::class)->recalculateTotals((int) $record['id_order']);
  }

}
