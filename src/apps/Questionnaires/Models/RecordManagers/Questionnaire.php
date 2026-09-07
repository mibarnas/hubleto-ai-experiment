<?php

namespace Hubleto\App\Custom\Questionnaires\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Questionnaire extends \Hubleto\Erp\RecordManager
{
  public $table = 'questionnaires';

  public function ATTENDEE(): BelongsTo
  {
    return $this->belongsTo(\Hubleto\App\Custom\Trainings\Models\RecordManagers\Attendee::class, 'id_attendee', 'id');
  }
}
