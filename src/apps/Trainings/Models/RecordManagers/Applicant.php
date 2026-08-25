<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Hubleto\App\Custom\Workers\Models\RecordManagers\Worker;
use Hubleto\App\Custom\Certificates\Models\RecordManagers\Certificate;

class Applicant extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_applicants';

  public function TRAINING_DATE(): BelongsTo
  {
    return $this->belongsTo(TrainingDate::class, 'id_training_date', 'id');
  }

  public function WORKER(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'id_worker', 'id');
  }

  public function ORDER(): BelongsTo
  {
    return $this->belongsTo(TrainingOrder::class, 'id_order', 'id');
  }

  public function QUESTIONNAIRE(): HasOne
  {
    return $this->hasOne(QuestionnaireAnswer::class, 'id_applicant', 'id');
  }

  public function CERTIFICATE(): HasOne
  {
    return $this->hasOne(Certificate::class, 'id_applicant', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idTrainingDate') > 0) {
      $query = $query->where($this->table . '.id_training_date', $hubleto->router()->urlParamAsInteger('idTrainingDate'));
    }
    if ($hubleto->router()->urlParamAsInteger('idOrder') > 0) {
      $query = $query->where($this->table . '.id_order', $hubleto->router()->urlParamAsInteger('idOrder'));
    }
    if ($hubleto->router()->urlParamAsInteger('idWorker') > 0) {
      $query = $query->where($this->table . '.id_worker', $hubleto->router()->urlParamAsInteger('idWorker'));
    }

    return $query;
  }

}
