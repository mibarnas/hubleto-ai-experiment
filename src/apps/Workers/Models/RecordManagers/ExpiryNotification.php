<?php

namespace Hubleto\App\Custom\Workers\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Hubleto\App\Community\Customers\Models\RecordManagers\Customer;

class ExpiryNotification extends \Hubleto\Erp\RecordManager
{
  public $table = 'expiry_notifications';

  public function WORKER(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'id_worker', 'id');
  }

  public function CUSTOMER(): BelongsTo
  {
    return $this->belongsTo(Customer::class, 'id_customer', 'id');
  }

}
