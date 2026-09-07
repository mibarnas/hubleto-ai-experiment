<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Schedule;
use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Workers\EmailLog;
use Hubleto\App\Custom\Workers\Mailer;

/**
 * Sends the meeting link of a training date to everybody registered for it.
 *
 * The message is identical for every attendee, so it goes out as one email
 * addressed to all of them (`a@x.sk,b@y.sk,...`) instead of one SMTP round trip
 * per person -- which is what made this slow on larger dates.
 */
class SendMeetingLink extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idSchedule = $this->router()->urlParamAsInteger('idSchedule');
    if ($idSchedule <= 0) throw new \Exception($this->translate('idSchedule is required.'));

    /** @var Schedule */
    $mSchedule = $this->getModel(Schedule::class);
    $schedule = $mSchedule->record->find($idSchedule);
    if (!$schedule) throw new \Exception($this->translate('Training date not found.'));
    if (empty($schedule->meeting_link)) throw new \Exception($this->translate('This training date has no meeting link set.'));

    /** @var EmailLog */
    $emailLog = $this->getService(EmailLog::class);

    // One query with a join instead of loading every attendee and then its
    // worker: only the two columns that are actually needed come back.
    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $rows = $mAttendee->record
      ->join('workers', 'workers.id', '=', 'attendees.id_worker')
      ->where('attendees.id_schedule', $idSchedule)
      ->get(['attendees.id as id_attendee', 'workers.email as email'])
    ;

    $recipients = [];
    $sentToIds = [];
    $failed = [];

    foreach ($rows as $row) {
      $address = Mailer::normalizeRecipients((string) $row->email);
      if (empty($address)) { $failed[] = (int) $row->id_attendee; continue; }

      $recipients[] = $address[0];
      $sentToIds[] = (int) $row->id_attendee;
    }

    $recipients = array_values(array_unique($recipients));

    if (empty($recipients)) {
      $emailLog->log('meeting-link.skipped', ['schedule' => $idSchedule, 'reason' => 'no valid recipient']);
      return [ 'status' => 'success', 'sent' => 0, 'failed' => $failed ];
    }

    $link = htmlspecialchars((string) $schedule->meeting_link);

    try {
      $this->getService(Mailer::class)->sendWithAttachment(
        $recipients,
        $this->translate('Meeting link for your upcoming training'),
        $this->translate('Join the training via the following link:') . ' <a href="' . $link . '">' . $link . '</a>'
      );
    } catch (\Throwable $e) {
      $emailLog->log('meeting-link.failed', [
        'schedule' => $idSchedule,
        'recipients' => count($recipients),
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }

    // A single bulk update rather than one write per attendee.
    $mAttendee->record->whereIn('id', $sentToIds)->update(['date_meeting_link_sent' => date('Y-m-d H:i:s')]);

    $emailLog->log('meeting-link.sent', [
      'schedule' => $idSchedule,
      'recipients' => count($recipients),
      'skipped' => count($failed),
      'to' => implode(';', $recipients),
    ]);

    return [ 'status' => 'success', 'sent' => count($sentToIds), 'failed' => $failed ];
  }
}
