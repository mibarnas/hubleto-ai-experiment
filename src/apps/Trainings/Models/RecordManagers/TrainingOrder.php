<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Hubleto\App\Community\Customers\Models\RecordManagers\Customer;
use Hubleto\App\Community\Contacts\Models\RecordManagers\Contact;
use Hubleto\App\Community\Settings\Models\RecordManagers\Currency;
use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Hubleto\App\Custom\Workers\Models\RecordManagers\Worker;

class TrainingOrder extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_orders';

  public function CUSTOMER(): BelongsTo
  {
    return $this->belongsTo(Customer::class, 'id_customer', 'id');
  }

  public function CONTACT(): BelongsTo
  {
    return $this->belongsTo(Contact::class, 'id_contact', 'id');
  }

  public function WORKER(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'id_worker', 'id');
  }

  public function TRAINING_DATE(): BelongsTo
  {
    return $this->belongsTo(TrainingDate::class, 'id_training_date', 'id');
  }

  public function CURRENCY(): BelongsTo
  {
    return $this->belongsTo(Currency::class, 'id_currency', 'id');
  }

  public function OWNER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_owner', 'id');
  }

  public function MANAGER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_manager', 'id');
  }

  public function APPLICANTS(): HasMany
  {
    return $this->hasMany(Applicant::class, 'id_order', 'id');
  }

}
