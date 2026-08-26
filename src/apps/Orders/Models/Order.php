<?php

namespace Hubleto\App\Custom\Orders\Models;

use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\File;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

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
  public ?string $lookupUrlDetail = 'orders/{%ID%}';
  public ?string $lookupUrlAdd = 'orders/add';

  public array $relations = [
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
    'CONTACT' => [ self::BELONGS_TO, Contact::class, 'id_contact', 'id' ],
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'SCHEDULE' => [ self::BELONGS_TO, Schedule::class, 'id_schedule', 'id' ],
    'CURRENCY' => [ self::BELONGS_TO, Currency::class, 'id_currency', 'id' ],
    'OWNER' => [ self::BELONGS_TO, User::class, 'id_owner', 'id' ],
    'MANAGER' => [ self::BELONGS_TO, User::class, 'id_manager', 'id' ],
    'ATTENDEES' => [ self::HAS_MANY, Attendee::class, 'id_order', 'id' ],
  ];

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
      'price' => (new Decimal($this, $this->translate('Price per person')))->setDecimals(2),
      'number_of_workers' => (new Integer($this, $this->translate('Number of workers')))->setReadonly()->setDefaultVisible(),
      'total_price' => (new Decimal($this, $this->translate('Total price')))->setDecimals(2)->setReadonly()->setDefaultVisible(),
      'id_currency' => (new Lookup($this, $this->translate('Currency'), Currency::class)),
      'date_ordered' => (new Date($this, $this->translate('Date ordered')))->setDefaultVisible()->setDefaultValue(date('Y-m-d')),
      'date_paid' => (new Date($this, $this->translate('Date paid')))->setDefaultVisible(),
      'file_workers' => (new File($this, $this->translate('Workers (.xlsx)')))->setFolderPath('order-workers'),
      'note' => (new Text($this, $this->translate('Note'))),
      'id_owner' => (new Lookup($this, $this->translate('Owner'), User::class))->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->getService(\Hubleto\Framework\AuthProvider::class)->getUserId()),
      'id_manager' => (new Lookup($this, $this->translate('Manager'), User::class))->setReactComponent('InputUserSelect'),
      'shared_with' => (new Json($this, $this->translate('Shared with')))->setReactComponent('InputSharedWith')->setTableCellRenderer('TableCellRendererSharedWith'),
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

    if (empty($record['price']) && !empty($record['id_schedule'])) {
      $schedule = $this->getModel(Schedule::class)->record->find($record['id_schedule']);
      if ($schedule) {
        $training = $this->getModel(\Hubleto\App\Custom\Trainings\Models\Training::class)->record->find($schedule->id_training);
        if ($training) $record['price'] = $training->price;
      }
    }

    return $record;
  }

  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);

    // Notifying admins is a side effect of placing an order, never a reason to
    // reject one -- an unconfigured mail account must not fail the save.
    try {
      $this->notifyAdministrators((int) $savedRecord['id']);
    } catch (\Throwable $e) {
      $this->logger()->error('Failed to notify administrators about a new training order: ' . $e->getMessage());
    }

    return $savedRecord;
  }

  /** Recomputes the denormalised worker count and total from the attendees. */
  public function recalculateTotals(int $idOrder): void
  {
    $order = $this->record->find($idOrder);
    if (!$order) return;

    $count = $this->getModel(Attendee::class)->record->where('id_order', $idOrder)->count();

    $this->record->find($idOrder)->update([
      'number_of_workers' => $count,
      'total_price' => round((float) $order->price * $count, 2),
    ]);
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
      $notificationSender->send(0, [], self::class, $idOrder, $idUser, $subject, $body, 'orders/' . $idOrder);
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
