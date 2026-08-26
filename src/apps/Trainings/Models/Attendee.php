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
use Hubleto\App\Custom\Questionnaires\Models\Questionnaire;

class Attendee extends \Hubleto\Erp\Model
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

  public string $table = 'attendees';
  public string $recordManagerClass = RecordManagers\Attendee::class;
  public ?string $lookupSqlValue = 'concat("Attendee #", {%TABLE%}.id)';
  public ?string $lookupUrlDetail = 'trainings/attendees/{%ID%}';

  public array $relations = [
    'SCHEDULE' => [ self::BELONGS_TO, Schedule::class, 'id_schedule', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'CERTIFICATE' => [ self::BELONGS_TO, Certificate::class, 'id_certificate', 'id' ],
    'QUESTIONNAIRE' => [ self::BELONGS_TO, Questionnaire::class, 'id_questionnaire', 'id' ],
    'ORDER' => [ self::BELONGS_TO, \Hubleto\App\Custom\Orders\Models\Order::class, 'id_order', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_schedule' => (new Lookup($this, $this->translate('Schedule'), Schedule::class))->setRequired()->setDefaultVisible()
        ->setDefaultValue($this->router()->urlParamAsInteger('idSchedule')),
      'id_worker' => (new Lookup($this, $this->translate('Worker'), Worker::class))->setRequired()->setDefaultVisible(),
      // The order's worker list is simply its attendees -- one source of truth.
      // Orders installs after Trainings, so the constraint is skipped.
      'id_order' => (new Lookup($this, $this->translate('Order'), \Hubleto\App\Custom\Orders\Models\Order::class))
        ->setDefaultValue($this->router()->urlParamAsInteger('idOrder'))
        ->setProperty('disableForeignKey', true)
      ,
      'id_certificate' => (new Lookup($this, $this->translate('Certificate'), Certificate::class))->setReadonly()->setDefaultVisible(),
      'id_questionnaire' => (new Lookup($this, $this->translate('Questionnaire'), Questionnaire::class))->setReadonly(),
      'is_passed' => (new Boolean($this, $this->translate('Passed')))->setDefaultVisible(),
      'date_registered' => (new Date($this, $this->translate('Date registered')))->setDefaultValue(date('Y-m-d')),
      'file_last_certificate' => (new File($this, $this->translate('Previous certificate')))->setFolderPath('previous-certificates'),
      'questionnaire_token' => (new Varchar($this, $this->translate('Questionnaire token')))->setReadonly()->setDefaultHidden(),
      'url_questionnaire' => (new Varchar($this, $this->translate('Questionnaire link')))->setReadonly()->setDefaultHidden(),
      'date_questionnaire_sent' => (new DateTime($this, $this->translate('Questionnaire sent on')))->setReadonly(),
      'date_questionnaire_filled' => (new DateTime($this, $this->translate('Questionnaire filled on')))->setReadonly()->setDefaultVisible(),
      'catalog_token' => (new Varchar($this, $this->translate('Catalog sheet token')))->setReadonly()->setDefaultHidden(),
      'date_catalog_filled' => (new DateTime($this, $this->translate('Catalog sheet filled on')))->setReadonly(),
      'education_level' => (new Integer($this, $this->translate('Highest education')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::EDUCATION_VALUES)),
      'financing_type' => (new Integer($this, $this->translate('Financing')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::FINANCING_VALUES)),
      'date_meeting_link_sent' => (new DateTime($this, $this->translate('Meeting link sent on')))->setReadonly(),
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
      'id_schedule__id_worker' => [
        'type' => 'unique',
        'columns' => [
          'id_schedule' => [ 'order' => 'asc' ],
          'id_worker' => [ 'order' => 'asc' ],
        ],
      ],
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Attendee');
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
    $this->refreshQuestionnaireUrl($savedRecord);
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
      $this->getModel(\Hubleto\App\Custom\Orders\Models\Order::class)->recalculateTotals($this->idOrderBeforeDelete);
    }
    return $id;
  }

  /** Stored so the one-time link is visible on the record, as the ERD expects. */
  private function refreshQuestionnaireUrl(array $record): void
  {
    if (empty($record['id']) || empty($record['questionnaire_token'])) return;
    $this->record->find($record['id'])->update([
      'url_questionnaire' => $this->env()->projectUrl . '/training-questionnaire?t=' . $record['questionnaire_token'],
    ]);
  }

  private function recalculateOrderTotals(array $record): void
  {
    if (empty($record['id_order'])) return;
    $this->getModel(\Hubleto\App\Custom\Orders\Models\Order::class)->recalculateTotals((int) $record['id_order']);
  }

}
