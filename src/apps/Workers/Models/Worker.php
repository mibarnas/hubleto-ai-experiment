<?php

namespace Hubleto\App\Custom\Workers\Models;

use Hubleto\Framework\Db\Column\Email;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Db\Column\Virtual;

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
    // Trainings installs after Workers, so these are declared with the fully
    // qualified class names rather than imports.
    'ATTENDEES' => [ self::HAS_MANY, \Hubleto\App\Custom\Trainings\Models\Attendee::class, 'id_worker', 'id' ],
    'CERTIFICATES' => [ self::HAS_MANY, \Hubleto\App\Custom\Trainings\Models\Certificate::class, 'id_worker', 'id' ],
    'ORDERS' => [ self::HAS_MANY, \Hubleto\App\Custom\TrainingOrders\Models\Order::class, 'id_worker', 'id' ],
    'EXPIRY_NOTIFICATIONS' => [ self::HAS_MANY, ExpiryNotification::class, 'id_worker', 'id' ],
  ];

  /**
   * The certificate that expires soonest among those not yet expired; when the
   * worker has only expired certificates, the one that expired most recently.
   * Reused by both retraining columns so they always describe the same
   * certificate.
   */
  private const SQL_NEXT_RETRAINING_CERTIFICATE = "
    select `c`.`id`
    from `certificates` `c`
    where `c`.`id_worker` = `workers`.`id`
      and `c`.`date_expiration` is not null
    order by (`c`.`date_expiration` < curdate()) asc, `c`.`date_expiration` asc
    limit 1
  ";

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

      // When the worker has to retrain, and for which training, is read from
      // the certificates every time it is shown. Storing it on the worker meant
      // the date silently went stale whenever a certificate was added, edited
      // or deleted without the recalculation running.
      'virt_date_next_retraining' => (new Virtual($this, $this->translate('Next retraining date')))->setDefaultVisible()
        ->setSearchAlgorithm('date')
        ->setProperty('sql', "
          select `c`.`date_expiration` from `certificates` `c`
          where `c`.`id` = (" . self::SQL_NEXT_RETRAINING_CERTIFICATE . ")
        "),
      'virt_next_retraining_training' => (new Virtual($this, $this->translate('Training to retake')))->setDefaultVisible()
        ->setProperty('sql', "
          select `t`.`name`
          from `certificates` `c`
          inner join `trainings` `t` on `t`.`id` = `c`.`id_training`
          where `c`.`id` = (" . self::SQL_NEXT_RETRAINING_CERTIFICATE . ")
        "),

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
