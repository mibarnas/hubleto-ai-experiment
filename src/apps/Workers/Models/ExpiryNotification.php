<?php

namespace Hubleto\App\Custom\Workers\Models;

use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Customers\Models\Customer;

class ExpiryNotification extends \Hubleto\Erp\Model
{
  const KIND_INDIVIDUAL_6_MONTHS = 1;
  const KIND_INDIVIDUAL_1_MONTH = 2;
  const KIND_COMPANY_YEARLY_DIGEST = 3;

  const KIND_VALUES = [
    self::KIND_INDIVIDUAL_6_MONTHS => '6 months before expiry (individual)',
    self::KIND_INDIVIDUAL_1_MONTH => '1 month before expiry (individual)',
    self::KIND_COMPANY_YEARLY_DIGEST => 'Yearly digest (company)',
  ];

  public string $table = 'training_expiry_notifications';
  public string $recordManagerClass = RecordManagers\ExpiryNotification::class;
  public ?string $lookupSqlValue = 'concat("Notification #", {%TABLE%}.id)';

  public array $relations = [
    'WORKER' => [ self::BELONGS_TO, Worker::class, 'id_worker', 'id' ],
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_worker' => (new Lookup($this, $this->translate('Worker'), Worker::class))->setDefaultVisible(),
      'id_certificate' => (new Lookup($this, $this->translate('Certificate'), \Hubleto\App\Custom\Certificates\Models\Certificate::class))
        ->setProperty('disableForeignKey', true),
      'id_customer' => (new Lookup($this, $this->translate('Company'), Customer::class))->setDefaultVisible(),
      'kind' => (new Integer($this, $this->translate('Kind')))->setEnumValues(array_map(fn($v) => $this->translate($v), self::KIND_VALUES))->setDefaultVisible()->setRequired(),
      'sent_on' => (new DateTime($this, $this->translate('Sent on')))->setDefaultVisible()->setDefaultValue(date('Y-m-d H:i:s')),
      'email_to' => (new Varchar($this, $this->translate('Sent to')))->setDefaultVisible(),
      'is_delivered' => (new Boolean($this, $this->translate('Delivered')))->setDefaultVisible()->setDefaultValue(true),
      'error' => (new Text($this, $this->translate('Error'))),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->show(['header', 'fulltextSearch', 'columnSearch']);
    $description->hide(['footer']);
    return $description;
  }

}
