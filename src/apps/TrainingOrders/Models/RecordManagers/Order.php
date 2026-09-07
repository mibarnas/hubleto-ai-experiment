<?php

namespace Hubleto\App\Custom\TrainingOrders\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Hubleto\App\Community\Customers\Models\RecordManagers\Customer;
use Hubleto\App\Community\Contacts\Models\RecordManagers\Contact;
use Hubleto\App\Community\Settings\Models\RecordManagers\Currency;
use Hubleto\App\Custom\Workers\Models\RecordManagers\Worker;
use Hubleto\App\Custom\Trainings\Models\RecordManagers\Schedule;
use Hubleto\App\Custom\Trainings\Models\RecordManagers\Attendee;

class Order extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_orders';

  public function CUSTOMER(): BelongsTo { return $this->belongsTo(Customer::class, 'id_customer', 'id'); }
  public function CONTACT(): BelongsTo { return $this->belongsTo(Contact::class, 'id_contact', 'id'); }
  public function WORKER(): BelongsTo { return $this->belongsTo(Worker::class, 'id_worker', 'id'); }
  public function SCHEDULE(): BelongsTo { return $this->belongsTo(Schedule::class, 'id_schedule', 'id'); }
  public function CURRENCY(): BelongsTo { return $this->belongsTo(Currency::class, 'id_currency', 'id'); }
  public function ATTENDEES(): HasMany { return $this->hasMany(Attendee::class, 'id_order', 'id'); }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    foreach (['idWorker' => 'id_worker', 'idCustomer' => 'id_customer', 'idSchedule' => 'id_schedule'] as $param => $column) {
      if ($hubleto->router()->urlParamAsInteger($param) > 0) {
        $query = $query->where($this->table . '.' . $column, $hubleto->router()->urlParamAsInteger($param));
      }
    }

    return $query;
  }
}
