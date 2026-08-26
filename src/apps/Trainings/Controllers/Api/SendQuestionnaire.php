<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Attendee;

class SendQuestionnaire extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idSchedule = $this->router()->urlParamAsInteger('idSchedule');
    if ($idSchedule <= 0) throw new \Exception('idSchedule is required.');

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendees = $mAttendee->record->where('id_schedule', $idSchedule)->with('WORKER')->get();

    $projectUrl = $this->env()->projectUrl;
    $sent = 0;
    $failed = [];

    foreach ($attendees as $attendee) {
      $worker = $attendee->WORKER;
      if (!$worker || empty($worker->email)) { $failed[] = $attendee->id; continue; }

      $questionnaireUrl = $projectUrl . '/training-questionnaire?t=' . $attendee->questionnaire_token;
      $catalogUrl = $projectUrl . '/training-catalog-sheet?t=' . $attendee->catalog_token;

      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Please fill in the satisfaction questionnaire'),
          $this->translate('Thank you for attending the training. Please fill in the satisfaction questionnaire') .
            ': <a href="' . htmlspecialchars($questionnaireUrl) . '">' . htmlspecialchars($questionnaireUrl) . '</a><br/>' .
            $this->translate('and the catalog sheet') . ': <a href="' . htmlspecialchars($catalogUrl) . '">' . htmlspecialchars($catalogUrl) . '</a>'
        );
        $mAttendee->record->find($attendee->id)->update(['date_questionnaire_sent' => date('Y-m-d H:i:s')]);
        $sent++;
      } catch (\Throwable $e) {
        $failed[] = $attendee->id;
      }
    }

    return [ 'status' => 'success', 'sent' => $sent, 'failed' => $failed ];
  }
}
