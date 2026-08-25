<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Applicant;

class SendQuestionnaire extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idTrainingDate = $this->router()->urlParamAsInteger('idTrainingDate');
    if ($idTrainingDate <= 0) throw new \Exception('idTrainingDate is required.');

    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    $applicants = $mApplicant->record->where('id_training_date', $idTrainingDate)->with('WORKER')->get();

    $projectUrl = $this->env()->projectUrl;
    $sent = 0;
    $failed = [];

    foreach ($applicants as $applicant) {
      $worker = $applicant->WORKER;
      if (!$worker || empty($worker->email)) { $failed[] = $applicant->id; continue; }

      $questionnaireUrl = $projectUrl . '/training-questionnaire?t=' . $applicant->questionnaire_token;
      $catalogUrl = $projectUrl . '/training-catalog-sheet?t=' . $applicant->catalog_token;

      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Please fill in the satisfaction questionnaire'),
          $this->translate('Thank you for attending the training. Please fill in the satisfaction questionnaire') .
            ': <a href="' . htmlspecialchars($questionnaireUrl) . '">' . htmlspecialchars($questionnaireUrl) . '</a><br/>' .
            $this->translate('and the catalog sheet') . ': <a href="' . htmlspecialchars($catalogUrl) . '">' . htmlspecialchars($catalogUrl) . '</a>'
        );
        $mApplicant->record->find($applicant->id)->update(['questionnaire_sent_on' => date('Y-m-d H:i:s')]);
        $sent++;
      } catch (\Throwable $e) {
        $failed[] = $applicant->id;
      }
    }

    return [ 'status' => 'success', 'sent' => $sent, 'failed' => $failed ];
  }
}
