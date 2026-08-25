<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;

class TrainingDate extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_dates';

  public function TRAINING(): BelongsTo
  {
    return $this->belongsTo(Training::class, 'id_training', 'id');
  }

  public function LECTURER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_lecturer', 'id');
  }

  public function APPLICANTS(): HasMany
  {
    return $this->hasMany(Applicant::class, 'id_training_date', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idTraining') > 0) {
      $query = $query->where($this->table . '.id_training', $hubleto->router()->urlParamAsInteger('idTraining'));
    }

    return $query;
  }

}
