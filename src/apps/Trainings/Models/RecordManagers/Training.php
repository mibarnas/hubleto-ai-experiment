<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Hubleto\App\Community\Settings\Models\RecordManagers\Company;

class Training extends \Hubleto\Erp\RecordManager
{
  public $table = 'trainings';

  public function COMPANY(): BelongsTo { return $this->belongsTo(Company::class, 'id_company', 'id'); }
  public function SCHEDULES(): HasMany { return $this->hasMany(Schedule::class, 'id_training', 'id'); }
}
