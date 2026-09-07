<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Workers\EmailLog;
use Hubleto\App\Custom\Workers\Mailer;

/**
 * Sends the catalog sheet + satisfaction questionnaire link to everybody
 * registered for a training date.
 *
 * Unlike the meeting link these cannot be bulk-sent: every attendee gets their
 * own one-time token, so the mails differ per recipient. The attendee list is
 * still fetched in a single joined query, and the "sent" timestamps are written
 * in one update at the end.
 */
class SendQuestionnaire extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idSchedule = $this->router()->urlParamAsInteger('idSchedule');
    if ($idSchedule <= 0) throw new \Exception($this->translate('idSchedule is required.'));

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $rows = $mAttendee->record
      ->join('workers', 'workers.id', '=', 'attendees.id_worker')
      ->where('attendees.id_schedule', $idSchedule)
      ->whereNull('attendees.date_questionnaire_filled')
      ->get([
        'attendees.id as id_attendee',
        'attendees.questionnaire_token as token',
        'workers.email as email',
        'workers.first_name as first_name',
      ])
    ;

    /** @var EmailLog */
    $emailLog = $this->getService(EmailLog::class);
    /** @var Mailer */
    $mailer = $this->getService(Mailer::class);

    $projectUrl = $this->env()->projectUrl;
    $sentToIds = [];
    $failed = [];

    foreach ($rows as $row) {
      $address = Mailer::normalizeRecipients((string) $row->email);
      if (empty($address) || empty($row->token)) { $failed[] = (int) $row->id_attendee; continue; }

      $url = $projectUrl . '/training-questionnaire?t=' . $row->token;

      try {
        $mailer->sendWithAttachment(
          $address,
          $this->translate('Please fill in the catalog sheet and the questionnaire'),
          $this->translate('Thank you for attending the training. Please fill in the catalog sheet and the satisfaction questionnaire') .
            ': <a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>'
        );
        $sentToIds[] = (int) $row->id_attendee;
      } catch (\Throwable $e) {
        $failed[] = (int) $row->id_attendee;
        $emailLog->log('questionnaire.failed', [
          'schedule' => $idSchedule,
          'attendee' => $row->id_attendee,
          'error' => $e->getMessage(),
        ]);
      }
    }

    if (!empty($sentToIds)) {
      $mAttendee->record->whereIn('id', $sentToIds)->update(['date_questionnaire_sent' => date('Y-m-d H:i:s')]);
    }

    $emailLog->log('questionnaire.sent', [
      'schedule' => $idSchedule,
      'sent' => count($sentToIds),
      'skipped' => count($failed),
    ]);

    return [ 'status' => 'success', 'sent' => count($sentToIds), 'failed' => $failed ];
  }
}
