<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\Community\Contacts\Models\Contact;
use Hubleto\App\Community\Settings\Models\Currency;
use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Custom\Workers\Models\Worker;

class TrainingOrder extends \Hubleto\Erp\Model
{
  const TYPE_PRIVATE = 1;
  const TYPE_COMPANY = 2;

  const TYPE_VALUES = [
    self::TYPE_PRIVATE => 'Private individual',
    self::TYPE_COMPANY => 'Company',
  ];

  public string $table = 'training_orders';
  public string $recordManagerClass = RecordManagers\TrainingOrder::class;
  public ?string $lookupSqlValue = '{%TABLE%}.identifier';
  public ?string $lookupUrlDetail = 'trainings/orders/{%ID%}';
  public ?string $lookupUrlAdd = 'trainings/orders/add';

  public array $relations = [
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
    'CONTACT' => [ self::BELONGS_TO, Contact::class, 'id_contact', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'TRAINING_DATE' => [ self::BELONGS_TO, TrainingDate::class, 'id_training_date', 'id' ],
    'CURRENCY' => [ self::BELONGS_TO, Currency::class, 'id_currency', 'id' ],
    'OWNER' => [ self::BELONGS_TO, User::class, 'id_owner', 'id' ],
    'MANAGER' => [ self::BELONGS_TO, User::class, 'id_manager', 'id' ],
    'APPLICANTS' => [ self::HAS_MANY, Applicant::class, 'id_order', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'identifier' => (new Varchar($this, $this->translate('Order number')))->setReadonly()->setDefaultVisible(),
      'order_type' => (new Integer($this, $this->translate('Order type')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::TYPE_VALUES))->setRequired()->setDefaultVisible()->setDefaultValue(self::TYPE_PRIVATE),
      'id_customer' => (new Lookup($this, $this->translate('Company'), Customer::class))->setDefaultVisible(),
      'id_contact' => (new Lookup($this, $this->translate('Contact person'), Contact::class)),
      'id_worker' => (new Lookup($this, $this->translate('Private individual'), Worker::class))->setDefaultVisible(),
      'id_training_date' => (new Lookup($this, $this->translate('Training date'), TrainingDate::class))->setRequired()->setDefaultVisible(),
      'price_per_person' => (new Decimal($this, $this->translate('Price per person')))->setDecimals(2),
      'number_of_applicants' => (new Integer($this, $this->translate('Number of applicants')))->setReadonly()->setDefaultVisible(),
      'total_price' => (new Decimal($this, $this->translate('Total price')))->setDecimals(2)->setReadonly()->setDefaultVisible(),
      'id_currency' => (new Lookup($this, $this->translate('Currency'), Currency::class)),
      'date_ordered' => (new Date($this, $this->translate('Date ordered')))->setDefaultVisible()->setDefaultValue(date('Y-m-d')),
      'date_paid' => (new Date($this, $this->translate('Date paid')))->setDefaultVisible(),
      'applicants_xlsx' => (new File($this, $this->translate('Applicants (.xlsx)')))->setFolderPath('order-applicants'),
      'note' => (new Text($this, $this->translate('Note'))),
      'id_owner' => (new Lookup($this, $this->translate('Owner'), User::class))->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->getService(\Hubleto\Framework\AuthProvider::class)->getUserId()),
      'id_manager' => (new Lookup($this, $this->translate('Manager'), User::class))->setReactComponent('InputUserSelect'),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Order');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    if (empty($record['identifier'])) {
      $record['identifier'] = 'TO-' . date('Y') . '-' . str_pad((string) ($this->record->max('id') + 1), 5, '0', STR_PAD_LEFT);
    }
    if (empty($record['price_per_person']) && !empty($record['id_training_date'])) {
      $mTrainingDate = $this->getModel(TrainingDate::class);
      $trainingDate = $mTrainingDate->record->find($record['id_training_date']);
      if ($trainingDate) {
        $mTraining = $this->getModel(Training::class);
        $training = $mTraining->record->find($trainingDate->id_training);
        if ($training) $record['price_per_person'] = $training->price_per_person;
      }
    }
    return $record;
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);
    $this->notifyAdministrators((int) $savedRecord['id']);
    return $savedRecord;
  }

  /**
   * Recalculates number_of_applicants and total_price from the currently
   * attached Applicant rows. Called after applicants are added/removed.
   */
  public function recalculateTotals(int $idOrder): void
  {
    $order = $this->record->find($idOrder);
    if (!$order) return;

    $mApplicant = $this->getModel(Applicant::class);
    $count = $mApplicant->record->where('id_order', $idOrder)->count();

    $this->record->find($idOrder)->update([
      'number_of_applicants' => $count,
      'total_price' => round((float) $order->price_per_person * $count, 2),
    ]);
  }

  /**
   * Notifies administrative users (users whose role grants the Trainings app)
   * that a new order was placed. Sender::send() no-ops without a session user,
   * so this also always sends an email regardless of session state.
   */
  private function notifyAdministrators(int $idOrder): void
  {
    $order = $this->record->find($idOrder);
    if (!$order) return;

    $adminUserIds = $this->getService(\Hubleto\App\Custom\Trainings\AdminUsers::class)->getAdminUserIds();
    $subject = $this->translate('New training order') . ' ' . $order->identifier;
    $body = $this->translate('A new training order has been created.') . ' #' . $order->identifier;

    $notificationSender = $this->getService(\Hubleto\App\Community\Notifications\Sender::class);
    foreach ($adminUserIds as $idUser) {
      $notificationSender->send(
        0,
        [],
        self::class,
        $idOrder,
        $idUser,
        $subject,
        $body,
        'trainings/orders/' . $idOrder
      );
    }

    /** @var User */
    $mUser = $this->getModel(User::class);
    $adminEmails = $mUser->record->whereIn('id', $adminUserIds)->pluck('email')->filter()->all();
    if (!empty($adminEmails)) {
      $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)
        ->sendWithAttachment(implode(',', $adminEmails), $subject, $body);
    }
  }

}
