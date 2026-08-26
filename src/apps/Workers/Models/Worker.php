<?php

namespace Hubleto\App\Custom\Workers\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Email;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\Community\Settings\Models\Country;
use Hubleto\App\Community\Auth\Models\User;

class Worker extends \Hubleto\Erp\Model
{
  const GENDER_MALE = 1;
  const GENDER_FEMALE = 2;
  const GENDER_UNSPECIFIED = 3;

  const GENDER_VALUES = [
    self::GENDER_MALE => 'Male',
    self::GENDER_FEMALE => 'Female',
    self::GENDER_UNSPECIFIED => 'Unspecified',
  ];

  public string $table = 'workers';
  public string $recordManagerClass = RecordManagers\Worker::class;
  public ?string $lookupSqlValue = 'concat(ifnull({%TABLE%}.first_name, ""), " ", ifnull({%TABLE%}.last_name, ""))';
  public ?string $lookupUrlDetail = 'workers/{%ID%}';
  public ?string $lookupUrlAdd = 'workers/add';

  public array $relations = [
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
    'COUNTRY' => [ self::BELONGS_TO, Country::class, 'id_country', 'id' ],
    'OWNER' => [ self::BELONGS_TO, User::class, 'id_owner', 'id' ],
    'MANAGER' => [ self::BELONGS_TO, User::class, 'id_manager', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_customer' => (new Lookup($this, $this->translate('Employer company'), Customer::class))->setDefaultVisible(),
      'title_before' => (new Varchar($this, $this->translate('Title before name'))),
      'first_name' => (new Varchar($this, $this->translate('First name')))->setRequired()->setDefaultVisible()->setIcon(self::COLUMN_NAME_DEFAULT_ICON),
      'last_name' => (new Varchar($this, $this->translate('Last name')))->setRequired()->setDefaultVisible(),
      'title_after' => (new Varchar($this, $this->translate('Title after name'))),
      'email' => (new Email($this, $this->translate('Email')))->setRequired()->setDefaultVisible(),
      'phone' => (new Varchar($this, $this->translate('Phone')))->setDefaultVisible(),
      'gender' => (new Integer($this, $this->translate('Gender')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::GENDER_VALUES))->setDefaultValue(self::GENDER_UNSPECIFIED),
      // The ERD's truncated `birth_c...` -- personal data, so hidden by default.
      'birth_number' => (new Varchar($this, $this->translate('Birth number')))->setDefaultHidden(),
      'address' => (new Varchar($this, $this->translate('Street'))),
      'city' => (new Varchar($this, $this->translate('City')))->setDefaultVisible(),
      'zip' => (new Varchar($this, $this->translate('ZIP'))),
      'id_country' => (new Lookup($this, $this->translate('Country'), Country::class)),
      'workplace_name' => (new Varchar($this, $this->translate('Workplace name'))),
      'workplace_address' => (new Varchar($this, $this->translate('Workplace street'))),
      'workplace_city' => (new Varchar($this, $this->translate('Workplace city'))),
      'workplace_zip' => (new Varchar($this, $this->translate('Workplace ZIP'))),
      // Denormalised so the Workers table can sort and filter on them.
      'date_next_retraining' => (new Date($this, $this->translate('Next retraining date')))->setReadonly()->setDefaultVisible(),
      'id_next_retraining_training' => (new Lookup($this, $this->translate('Training to retake'), \Hubleto\App\Custom\Trainings\Models\Training::class))
        ->setReadonly()
        ->setDefaultVisible()
        // Trainings installs after Workers, so the constraint is skipped.
        ->setProperty('disableForeignKey', true)
      ,
      'note' => (new Text($this, $this->translate('Note'))),
      'id_owner' => (new Lookup($this, $this->translate('Owner'), User::class))->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->getService(\Hubleto\Framework\AuthProvider::class)->getUserId()),
      'id_manager' => (new Lookup($this, $this->translate('Manager'), User::class))->setReactComponent('InputUserSelect'),
    ]);
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'email' => [
        'type' => 'unique',
        'columns' => [ 'email' => [ 'order' => 'asc' ] ],
      ],
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Worker');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    if (isset($record['email'])) $record['email'] = strtolower(trim($record['email']));
    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    if (isset($record['email'])) $record['email'] = strtolower(trim($record['email']));
    return $record;
  }

}
