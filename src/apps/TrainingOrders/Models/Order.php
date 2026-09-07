<?php

namespace Hubleto\App\Custom\TrainingOrders\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Db\Column\Virtual;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\Community\Contacts\Models\Contact;
use Hubleto\App\Community\Settings\Models\Currency;
use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Trainings\Models\Schedule;
use Hubleto\App\Custom\Trainings\Models\Attendee;

class Order extends \Hubleto\Erp\Model
{
  const TYPE_PRIVATE = 1;
  const TYPE_COMPANY = 2;

  const TYPE_VALUES = [
    self::TYPE_PRIVATE => 'Private individual',
    self::TYPE_COMPANY => 'Company',
  ];

  /**
   * The community Orders app already owns `orders` (with foreign keys from
   * Invoices, Projects, Deals and Quotes), so this table is namespaced.
   */
  public string $table = 'training_orders';
  public string $recordManagerClass = RecordManagers\Order::class;
  public ?string $lookupSqlValue = '{%TABLE%}.identifier';
  public ?string $lookupUrlDetail = 'training-orders/{%ID%}';
  public ?string $lookupUrlAdd = 'training-orders/add';

  public array $relations = [
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
    'CONTACT' => [ self::BELONGS_TO, Contact::class, 'id_contact', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'SCHEDULE' => [ self::BELONGS_TO, Schedule::class, 'id_schedule', 'id' ],
    'CURRENCY' => [ self::BELONGS_TO, Currency::class, 'id_currency', 'id' ],
    'ATTENDEES' => [ self::HAS_MANY, Attendee::class, 'id_order', 'id' ],
  ];

  /**
   * Price per person, read through the booked schedule's training. Reused by
   * the per-person and the total column so the two can never disagree.
   */
  private const SQL_PRICE_PER_PERSON = "
    select `t`.`price`
    from `schedules` `s`
    inner join `trainings` `t` on `t`.`id` = `s`.`id_training`
    where `s`.`id` = `training_orders`.`id_schedule`
  ";

  private const SQL_WORKER_COUNT = "
    select count(`a`.`id`) from `attendees` `a`
    where `a`.`id_order` = `training_orders`.`id`
  ";

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'identifier' => (new Varchar($this, $this->translate('Order number')))->setReadonly()->setDefaultVisible(),
      'order_type' => (new Integer($this, $this->translate('Order type')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::TYPE_VALUES))->setRequired()->setDefaultVisible()->setDefaultValue(self::TYPE_PRIVATE),
      'id_customer' => (new Lookup($this, $this->translate('Company'), Customer::class))->setDefaultVisible(),
      'id_contact' => (new Lookup($this, $this->translate('Contact person'), Contact::class)),
      'id_worker' => (new Lookup($this, $this->translate('Private individual'), Worker::class))->setDefaultVisible(),
      // Not in the ERD, but the order has to know which schedule was booked so
      // its workers can be enrolled as attendees.
      'id_schedule' => (new Lookup($this, $this->translate('Schedule'), Schedule::class))->setRequired()->setDefaultVisible(),

      // Price, head count and total are read at display time instead of being
      // written into the record: the price belongs to the training and the head
      // count is simply how many attendees the order has, so a stored copy
      // could only ever go stale.
      'virt_price' => (new Virtual($this, $this->translate('Price per person')))->setDefaultVisible()
        ->setSearchAlgorithm('number')
        ->setProperty('sql', self::SQL_PRICE_PER_PERSON),
      'virt_number_of_workers' => (new Virtual($this, $this->translate('Number of workers')))->setDefaultVisible()
        ->setSearchAlgorithm('number')
        ->setProperty('sql', self::SQL_WORKER_COUNT),
      'virt_total_price' => (new Virtual($this, $this->translate('Total price')))->setDefaultVisible()
        ->setSearchAlgorithm('number')
        ->setProperty('sql', 'select round(ifnull((' . self::SQL_PRICE_PER_PERSON . '), 0) * (' . self::SQL_WORKER_COUNT . '), 2)'),

      'id_currency' => (new Lookup($this, $this->translate('Currency'), Currency::class)),
      'date_ordered' => (new Date($this, $this->translate('Date ordered')))->setDefaultVisible()->setDefaultValue(date('Y-m-d')),
      'date_paid' => (new Date($this, $this->translate('Date paid')))->setDefaultVisible(),
      'file_workers' => (new File($this, $this->translate('Workers (.xlsx)')))->setFolderPath('order-workers'),
      'note' => (new Text($this, $this->translate('Note'))),
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
      $record['identifier'] = $this->generateIdentifier();
    }

    $record = $this->clearUnusedOrdererFields($record);

    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    return $this->clearUnusedOrdererFields($record);
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);

    $this->enrolPrivateIndividual($savedRecord);

    // Notifying admins is a side effect of placing an order, never a reason to
    // reject one -- an unconfigured mail account must not fail the save.
    try {
      $this->notifyAdministrators((int) $savedRecord['id']);
    } catch (\Throwable $e) {
      $this->logger()->error('Failed to notify administrators about a new training order: ' . $e->getMessage());
    }

    return $savedRecord;
  }

  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);
    $this->enrolPrivateIndividual($savedRecord);
    return $savedRecord;
  }

  /**
   * A private individual is the single attendee of their own order. Enrolling
   * them here keeps "the workers on an order are its attendees" true for both
   * order types, which is what the head count and the total price rely on.
   */
  private function enrolPrivateIndividual(array $record): void
  {
    if ((int) ($record['order_type'] ?? 0) !== self::TYPE_PRIVATE) return;
    if (empty($record['id_worker']) || empty($record['id_schedule'])) return;

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);

    $existing = $mAttendee->record
      ->where('id_schedule', $record['id_schedule'])
      ->where('id_worker', $record['id_worker'])
      ->first()
    ;

    if ($existing) {
      if ((int) $existing->id_order !== (int) $record['id']) {
        $mAttendee->record->find($existing->id)->update(['id_order' => $record['id']]);
      }
      return;
    }

    $mAttendee->record->recordCreate([
      'id_schedule' => $record['id_schedule'],
      'id_worker' => $record['id_worker'],
      'id_order' => $record['id'],
      'date_registered' => $record['date_ordered'] ?? date('Y-m-d'),
    ]);
  }

  public function generateIdentifier(): string
  {
    return 'TO-' . date('Y') . '-' . str_pad((string) ((int) $this->record->max('id') + 1), 5, '0', STR_PAD_LEFT);
  }

  /** Price per person of this order, taken from the booked training. */
  public function getPricePerPerson(int $idOrder): float
  {
    $order = $this->record->find($idOrder);
    if (!$order || empty($order->id_schedule)) return 0.0;

    $schedule = $this->getModel(Schedule::class)->record->find($order->id_schedule);
    if (!$schedule) return 0.0;

    $training = $this->getModel(\Hubleto\App\Custom\Trainings\Models\Training::class)->record->find($schedule->id_training);

    return $training ? (float) $training->price : 0.0;
  }

  /**
   * An order is placed either by a company or by a private individual. The two
   * sets of fields are mutually exclusive, so whichever side is not in use is
   * cleared on save -- otherwise switching the type in the form leaves the
   * previous orderer silently attached to the record.
   */
  private function clearUnusedOrdererFields(array $record): array
  {
    if (!isset($record['order_type'])) return $record;

    if ((int) $record['order_type'] === self::TYPE_COMPANY) {
      $record['id_worker'] = null;
    } else {
      $record['id_customer'] = null;
      $record['id_contact'] = null;
    }

    return $record;
  }

  private function notifyAdministrators(int $idOrder): void
  {
    $order = $this->record->find($idOrder);
    if (!$order) return;

    $adminUserIds = $this->getService(\Hubleto\App\Custom\Trainings\AdminUsers::class)->getAdminUserIds();
    $subject = $this->translate('New training order') . ' ' . $order->identifier;
    $body = $this->translate('A new training order has been created.') . ' #' . $order->identifier;

    $notificationSender = $this->getService(\Hubleto\App\Community\Notifications\Sender::class);
    foreach ($adminUserIds as $idUser) {
      $notificationSender->send(0, [], self::class, $idOrder, $idUser, $subject, $body, 'training-orders/' . $idOrder);
    }

    /** @var User */
    $mUser = $this->getModel(User::class);
    $adminEmails = $mUser->record->whereIn('id', $adminUserIds)->pluck('email')->filter()->all();
    if (!empty($adminEmails)) {
      $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)
        ->sendWithAttachment($adminEmails, $subject, $body);
    }
  }

}
