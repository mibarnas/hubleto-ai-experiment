<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Hubleto\App\Custom\Workers\Models\RecordManagers\Worker;
use Hubleto\App\Custom\Questionnaires\Models\RecordManagers\Questionnaire;
use Hubleto\App\Custom\Orders\Models\RecordManagers\Order;

class Attendee extends \Hubleto\Erp\RecordManager
{
  public $table = 'attendees';

  public function SCHEDULE(): BelongsTo { return $this->belongsTo(Schedule::class, 'id_schedule', 'id'); }
  public function WORKER(): BelongsTo { return $this->belongsTo(Worker::class, 'id_worker', 'id'); }
  public function CERTIFICATE(): BelongsTo { return $this->belongsTo(Certificate::class, 'id_certificate', 'id'); }
  public function QUESTIONNAIRE(): BelongsTo { return $this->belongsTo(Questionnaire::class, 'id_questionnaire', 'id'); }
  public function ORDER(): BelongsTo { return $this->belongsTo(Order::class, 'id_order', 'id'); }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    foreach (['idSchedule' => 'id_schedule', 'idOrder' => 'id_order', 'idWorker' => 'id_worker'] as $param => $column) {
      if ($hubleto->router()->urlParamAsInteger($param) > 0) {
        $query = $query->where($this->table . '.' . $column, $hubleto->router()->urlParamAsInteger($param));
      }
    }

    return $query;
  }
}
