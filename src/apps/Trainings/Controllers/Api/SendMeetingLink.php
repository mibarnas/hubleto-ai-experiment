<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\TrainingDate;
use Hubleto\App\Custom\Trainings\Models\Applicant;

class SendMeetingLink extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idTrainingDate = $this->router()->urlParamAsInteger('idTrainingDate');
    if ($idTrainingDate <= 0) throw new \Exception('idTrainingDate is required.');

    /** @var TrainingDate */
    $mTrainingDate = $this->getModel(TrainingDate::class);
    $trainingDate = $mTrainingDate->record->find($idTrainingDate);
    if (!$trainingDate) throw new \Exception('Training date not found.');
    if (empty($trainingDate->teams_link)) throw new \Exception('This training date has no Teams meeting link set.');

    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    $applicants = $mApplicant->record->where('id_training_date', $idTrainingDate)->with('WORKER')->get();

    $sent = 0;
    $failed = [];
    foreach ($applicants as $applicant) {
      $worker = $applicant->WORKER;
      if (!$worker || empty($worker->email)) { $failed[] = $applicant->id; continue; }

      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Meeting link for your upcoming training'),
          $this->translate('Join the training via the following link:') . ' <a href="' . htmlspecialchars($trainingDate->teams_link) . '">' . htmlspecialchars($trainingDate->teams_link) . '</a>'
        );
        $mApplicant->record->find($applicant->id)->update(['meeting_link_sent_on' => date('Y-m-d H:i:s')]);
        $sent++;
      } catch (\Throwable $e) {
        $failed[] = $applicant->id;
      }
    }

    return [ 'status' => 'success', 'sent' => $sent, 'failed' => $failed ];
  }
}
