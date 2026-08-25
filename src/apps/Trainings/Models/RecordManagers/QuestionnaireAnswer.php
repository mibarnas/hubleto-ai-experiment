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

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();

    if ($hubleto->router()->urlParamAsInteger('idApplicant') > 0) {
      $query = $query->where($this->table . '.id_applicant', $hubleto->router()->urlParamAsInteger('idApplicant'));
    }

    return $query;
  }

}
