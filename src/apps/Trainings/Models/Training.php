<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Settings\Models\Company;

class Training extends \Hubleto\Erp\Model
{
  public string $table = 'trainings';
  public string $recordManagerClass = RecordManagers\Training::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlDetail = 'trainings/{%ID%}';
  public ?string $lookupUrlAdd = 'trainings/add';

  public array $relations = [
    'COMPANY' => [ self::BELONGS_TO, Company::class, 'id_company', 'id' ],
    'SCHEDULES' => [ self::HAS_MANY, Schedule::class, 'id_training', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Training name')))->setRequired()->setDefaultVisible()->setCssClass('text-2xl text-primary')->setIcon(self::COLUMN_NAME_DEFAULT_ICON),
      'number' => (new Varchar($this, $this->translate('Training number')))->setDefaultVisible(),
      'price' => (new Decimal($this, $this->translate('Price per person')))->setDecimals(2)->setDefaultVisible(),
      'interval' => (new Integer($this, $this->translate('Retraining interval (years)')))->setDefaultVisible(),
      'id_company' => (new Lookup($this, $this->translate('Company'), Company::class))->setDefaultVisible(),
      'template' => (new File($this, $this->translate('Certificate template (.docx)')))
        ->setFolderPath('certificate-templates')
        ->setRenamePattern('{%FILENAME_ASCII%}-{%TS%}.{%EXT%}')
        ->setDefaultVisible()
      ,
      // Result of DocxTemplate::analyse(), refreshed whenever the template
      // changes: which values the template uses, which it asks for but we
      // cannot supply, and which required ones it is missing.
      'template_params' => (new Json($this, $this->translate('Template check')))->setReadonly()->setDefaultHidden(),
      'description' => (new Text($this, $this->translate('Description'))),
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
    if (($originalRecord['template'] ?? null) !== ($savedRecord['template'] ?? null)) {
      $this->refreshTemplateParams((int) $savedRecord['id']);
    }
    return $savedRecord;
  }

  /**
   * Scans the uploaded certificate template for `<< value >>` placeholders and
   * stores the analysis, so the client can replace templates without any code
   * change and is told straight away when a template is wrong.
   */
  public function refreshTemplateParams(int $idTraining): void
  {
    $record = $this->record->find($idTraining);
    if (!$record) return;

    if (empty($record->template)) {
      $this->record->find($idTraining)->update(['template_params' => null]);
      return;
    }

    $filePath = $this->env()->uploadFolder . '/' . $record->template;

    if (!is_file($filePath)) {
      $this->storeTemplateError($idTraining, 'The uploaded template file could not be found on the server.');
      return;
    }

    try {
      $analysis = (new \Hubleto\App\Custom\Trainings\DocxTemplate($filePath))->analyse();
      $this->record->find($idTraining)->update([
        'template_params' => json_encode($analysis, JSON_UNESCAPED_UNICODE),
      ]);
    } catch (\Throwable $e) {
      $this->logger()->error('Failed to scan certificate template placeholders: ' . $e->getMessage());
      $this->storeTemplateError($idTraining, $e->getMessage());
    }
  }

  private function storeTemplateError(int $idTraining, string $message): void
  {
    $this->record->find($idTraining)?->update([
      'template_params' => json_encode([
        'found' => [],
        'unknown' => [],
        'missingRequired' => [],
        'raw' => [],
        'error' => $message,
      ], JSON_UNESCAPED_UNICODE),
    ]);
  }

}
