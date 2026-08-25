<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Settings\Models\Company;
use Hubleto\App\Community\Settings\Models\Currency;
use Hubleto\App\Community\Auth\Models\User;

class Training extends \Hubleto\Erp\Model
{
  public string $table = 'trainings';
  public string $recordManagerClass = RecordManagers\Training::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlDetail = 'trainings/{%ID%}';
  public ?string $lookupUrlAdd = 'trainings/add';

  public array $relations = [
    'COMPANY' => [ self::BELONGS_TO, Company::class, 'id_company', 'id' ],
    'CURRENCY' => [ self::BELONGS_TO, Currency::class, 'id_currency', 'id' ],
    'OWNER' => [ self::BELONGS_TO, User::class, 'id_owner', 'id' ],
    'MANAGER' => [ self::BELONGS_TO, User::class, 'id_manager', 'id' ],
    'DATES' => [ self::HAS_MANY, TrainingDate::class, 'id_training', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Training name')))->setRequired()->setDefaultVisible()->setCssClass('text-2xl text-primary')->setIcon(self::COLUMN_NAME_DEFAULT_ICON),
      'training_number' => (new Varchar($this, $this->translate('Training number')))->setDefaultVisible(),
      'price_per_person' => (new Decimal($this, $this->translate('Price per person')))->setDecimals(2)->setDefaultVisible(),
      'id_currency' => (new Lookup($this, $this->translate('Currency'), Currency::class))->setDefaultVisible(),
      'retraining_interval_years' => (new Integer($this, $this->translate('Retraining interval (years)')))->setDefaultVisible(),
      'id_company' => (new Lookup($this, $this->translate('Company'), Company::class))->setDefaultVisible(),
      'certificate_template' => (new File($this, $this->translate('Certificate template (.docx)')))
        ->setFolderPath('certificate-templates')
        ->setRenamePattern('{%FILENAME_ASCII%}-{%TS%}.{%EXT%}')
        ->setDefaultVisible()
      ,
      'certificate_template_params' => (new Json($this, $this->translate('Discovered template parameters')))->setReadonly(),
      'ruvz_name' => (new Varchar($this, $this->translate('RÚVZ name (default)'))),
      'ruvz_certificate_number' => (new Varchar($this, $this->translate('RÚVZ certificate number (default)'))),
      'ruvz_date_issued' => (new Date($this, $this->translate('RÚVZ date issued (default)'))),
      'is_active' => (new Boolean($this, $this->translate('Active')))->setDefaultVisible()->setDefaultValue(true),
      'description' => (new Text($this, $this->translate('Description'))),
      'id_owner' => (new Lookup($this, $this->translate('Owner'), User::class))->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->getService(\Hubleto\Framework\AuthProvider::class)->getUserId()),
      'id_manager' => (new Lookup($this, $this->translate('Manager'), User::class))->setReactComponent('InputUserSelect'),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Training');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);
    $this->refreshTemplateParams((int) $savedRecord['id']);
    return $savedRecord;
  }

  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);
    if (($originalRecord['certificate_template'] ?? null) !== ($savedRecord['certificate_template'] ?? null)) {
      $this->refreshTemplateParams((int) $savedRecord['id']);
    }
    return $savedRecord;
  }

  /**
   * Scans the uploaded certificate template for `<placeholder>` parameters and
   * stores the discovered list, so templates stay replaceable without code changes.
   */
  public function refreshTemplateParams(int $idTraining): void
  {
    $record = $this->record->find($idTraining);
    if (!$record || empty($record->certificate_template)) return;

    $filePath = $this->env()->uploadFolder . '/' . $record->certificate_template;
    if (!is_file($filePath)) return;

    try {
      $params = (new \Hubleto\App\Custom\Certificates\DocxTemplate($filePath))->scanPlaceholders();
      $this->record->find($idTraining)->update([
        'certificate_template_params' => json_encode(array_values($params)),
      ]);
    } catch (\Throwable $e) {
      $this->logger()->error('Failed to scan certificate template placeholders: ' . $e->getMessage());
    }
  }

}
