<?php

namespace Hubleto\App\Custom\Workers\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Hubleto\App\Community\Customers\Models\RecordManagers\Customer;
use Hubleto\App\Community\Settings\Models\RecordManagers\Country;
use Hubleto\App\Community\Auth\Models\RecordManagers\User;

class Worker extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_workers';

  public function CUSTOMER(): BelongsTo
  {
    return $this->belongsTo(Customer::class, 'id_customer', 'id');
  }

  public function COUNTRY(): BelongsTo
  {
    return $this->belongsTo(Country::class, 'id_country', 'id');
  }

  public function OWNER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_owner', 'id');
  }

  public function MANAGER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_manager', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idCustomer') > 0) {
      $query = $query->where($this->table . '.id_customer', $hubleto->router()->urlParamAsInteger('idCustomer'));
    }

    return $query;
  }

}
