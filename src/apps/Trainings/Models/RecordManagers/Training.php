<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Hubleto\App\Community\Settings\Models\RecordManagers\Company;
use Hubleto\App\Community\Settings\Models\RecordManagers\Currency;
use Hubleto\App\Community\Auth\Models\RecordManagers\User;

class Training extends \Hubleto\Erp\RecordManager
{
  public $table = 'trainings';

  public function COMPANY(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'id_company', 'id');
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

  public function DATES(): HasMany
  {
    return $this->hasMany(TrainingDate::class, 'id_training', 'id');
  }

}
