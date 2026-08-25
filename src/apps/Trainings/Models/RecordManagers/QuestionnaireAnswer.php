<?php

namespace Hubleto\App\Custom\Trainings\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireAnswer extends \Hubleto\Erp\RecordManager
{
  public $table = 'training_questionnaire_answers';

  public function APPLICANT(): BelongsTo
  {
    return $this->belongsTo(Applicant::class, 'id_applicant', 'id');
  }

}
