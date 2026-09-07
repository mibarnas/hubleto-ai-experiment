<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends \Hubleto\Erp\RecordManager
{
  public $table = 'schedules';

  public function TRAINING(): BelongsTo { return $this->belongsTo(Training::class, 'id_training', 'id'); }
  public function ATTENDEES(): HasMany { return $this->hasMany(Attendee::class, 'id_schedule', 'id'); }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idTraining') > 0) {
      $query = $query->where($this->table . '.id_training', $hubleto->router()->urlParamAsInteger('idTraining'));
    }

    if ($hubleto->router()->urlParamAsInteger('idWorker') > 0) {
      $idWorker = $hubleto->router()->urlParamAsInteger('idWorker');
      $query = $query->whereHas('ATTENDEES', fn($q) => $q->where('id_worker', $idWorker));
    }

    return $query;
  }
}
