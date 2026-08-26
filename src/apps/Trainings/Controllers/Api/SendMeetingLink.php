<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Schedule;
use Hubleto\App\Custom\Trainings\Models\Attendee;

class SendMeetingLink extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idSchedule = $this->router()->urlParamAsInteger('idSchedule');
    if ($idSchedule <= 0) throw new \Exception('idSchedule is required.');

    /** @var Schedule */
    $mSchedule = $this->getModel(Schedule::class);
    $schedule = $mSchedule->record->find($idSchedule);
    if (!$schedule) throw new \Exception('Training date not found.');
    if (empty($schedule->meeting_link)) throw new \Exception('This training date has no Teams meeting link set.');

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendees = $mAttendee->record->where('id_schedule', $idSchedule)->with('WORKER')->get();

    $sent = 0;
    $failed = [];
    foreach ($attendees as $attendee) {
      $worker = $attendee->WORKER;
      if (!$worker || empty($worker->email)) { $failed[] = $attendee->id; continue; }

      try {
        $this->getService(\Hubleto\App\Custom\Workers\Mailer::class)->sendWithAttachment(
          $worker->email,
          $this->translate('Meeting link for your upcoming training'),
          $this->translate('Join the training via the following link:') . ' <a href="' . htmlspecialchars($schedule->meeting_link) . '">' . htmlspecialchars($schedule->meeting_link) . '</a>'
        );
        $mAttendee->record->find($attendee->id)->update(['date_meeting_link_sent' => date('Y-m-d H:i:s')]);
        $sent++;
      } catch (\Throwable $e) {
        $failed[] = $attendee->id;
      }
    }

    return [ 'status' => 'success', 'sent' => $sent, 'failed' => $failed ];
  }
}
