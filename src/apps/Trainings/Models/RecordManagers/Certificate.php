<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Hubleto\App\Custom\Workers\Models\RecordManagers\Worker;
use Hubleto\App\Community\Documents\Models\RecordManagers\Document;

class Certificate extends \Hubleto\Erp\RecordManager
{
  public $table = 'certificates';

  public function WORKER(): BelongsTo { return $this->belongsTo(Worker::class, 'id_worker', 'id'); }
  public function TRAINING(): BelongsTo { return $this->belongsTo(Training::class, 'id_training', 'id'); }
  public function DOCUMENT(): BelongsTo { return $this->belongsTo(Document::class, 'id_document', 'id'); }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idWorker') > 0) {
      $query = $query->where($this->table . '.id_worker', $hubleto->router()->urlParamAsInteger('idWorker'));
    }
    if ($hubleto->router()->urlParamAsInteger('idTraining') > 0) {
      $query = $query->where($this->table . '.id_training', $hubleto->router()->urlParamAsInteger('idTraining'));
    }

    return $query;
  }
}
