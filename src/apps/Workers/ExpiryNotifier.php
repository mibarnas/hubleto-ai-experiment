<?php

namespace Hubleto\App\Custom\Workers;

use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Workers\Models\ExpiryNotification;
use Hubleto\App\Custom\Trainings\Models\Certificate;
use Hubleto\App\Community\Contacts\Models\Value as ContactValue;
use Hubleto\App\Community\Contacts\Models\Contact;

/**
 * Checks worker certificate validity daily and sends retraining reminders.
 * Individuals get a reminder 6 months before expiry, then again 1 month
 * before if they have not registered for a matching training in the
 * meantime. Company-ordered workers instead get one yearly digest per
 * customer, sent at the start of the year. Every send is recorded in
 * ExpiryNotification first so re-running the cron never double-sends.
 */
class ExpiryNotifier extends \Hubleto\Erp\Core
{
  public function run(\DateTimeImmutable $today): void
  {
    $this->runIndividualReminders($today);
    $this->runCompanyYearlyDigests($today);
  }

  private function runIndividualReminders(\DateTimeImmutable $today): void
  {
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    /** @var ExpiryNotification */
    $mExpiryNotification = $this->getModel(ExpiryNotification::class);

    $sixMonths = $today->modify('+6 months')->format('Y-m-d');
    $oneMonth = $today->modify('+1 month')->format('Y-m-d');

    $certificates = $mCertificate->record
      ->whereNotNull('date_expiration')
      ->whereIn('date_expiration', [$sixMonths, $oneMonth])
      ->get();

    foreach ($certificates as $certificate) {
      $worker = $mWorker->record->find($certificate->id_worker);
      if (!$worker || !empty($worker->id_customer)) continue; // company-ordered workers use the digest path

      $kind = $certificate->date_expiration === $sixMonths
        ? ExpiryNotification::KIND_INDIVIDUAL_6_MONTHS
        : ExpiryNotification::KIND_INDIVIDUAL_1_MONTH;

      $alreadySent = $mExpiryNotification->record
        ->where('id_certificate', $certificate->id)
        ->where('kind', $kind)
        ->exists();
      if ($alreadySent) continue;

      if ($kind === ExpiryNotification::KIND_INDIVIDUAL_1_MONTH && $this->hasUpcomingRegistration($worker->id, $certificate->id_training)) {
        continue;
      }

      $this->send(
        $worker->email,
        $this->translate('Your training certificate is expiring soon'),
        $this->translate('Dear') . ' ' . $worker->first_name . ' ' . $worker->last_name . ', ' .
          $this->translate('your certificate is valid until') . ' ' . $certificate->date_expiration . '.',
        $kind,
        (int) $worker->id,
        (int) $certificate->id,
        0
      );
    }
  }

  private function runCompanyYearlyDigests(\DateTimeImmutable $today): void
  {
    if ($today->format('m-d') !== '01-01') return; // beginning of the year only

    /** @var ExpiryNotification */
    $mExpiryNotification = $this->getModel(ExpiryNotification::class);
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);
    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);

    $year = $today->format('Y');
    $certificates = $mCertificate->record
      ->whereNotNull('date_expiration')
      ->whereRaw('YEAR(date_expiration) = ?', [$year])
      ->get();

    $byCustomer = [];
    foreach ($certificates as $certificate) {
      $worker = $mWorker->record->find($certificate->id_worker);
      if (!$worker || empty($worker->id_customer)) continue;
      $byCustomer[$worker->id_customer][] = $worker;
    }

    foreach ($byCustomer as $idCustomer => $workers) {
      $alreadySent = $mExpiryNotification->record
        ->where('id_customer', $idCustomer)
        ->where('kind', ExpiryNotification::KIND_COMPANY_YEARLY_DIGEST)
        ->whereRaw('YEAR(sent_on) = ?', [$year])
        ->exists();
      if ($alreadySent) continue;

      $email = $this->getPrimaryContactEmail((int) $idCustomer);
      $list = implode("\n", array_map(fn($w) => $w->first_name . ' ' . $w->last_name, $workers));

      $this->send(
        $email,
        $this->translate('Workers with training validity ending this year'),
        $this->translate('The following workers need retraining in') . " {$year}:\n{$list}",
        ExpiryNotification::KIND_COMPANY_YEARLY_DIGEST,
        0,
        0,
        (int) $idCustomer
      );
    }
  }

  private function hasUpcomingRegistration(int $idWorker, int $idTraining): bool
  {
    /** @var \Hubleto\App\Custom\Trainings\Models\Attendee */
    $mAttendee = $this->getModel(\Hubleto\App\Custom\Trainings\Models\Attendee::class);
    return $mAttendee->record
      ->where('id_worker', $idWorker)
      ->whereHas('TRAINING_DATE', function ($q) use ($idTraining) {
        $q->where('id_training', $idTraining)->where('date_start', '>=', date('Y-m-d H:i:s'));
      })
      ->exists();
  }

  private function getPrimaryContactEmail(int $idCustomer): string
  {
    /** @var Contact */
    $mContact = $this->getModel(Contact::class);
    $contact = $mContact->record->where('id_customer', $idCustomer)->orderByDesc('is_primary')->first();
    if (!$contact) return '';

    /** @var ContactValue */
    $mContactValue = $this->getModel(ContactValue::class);
    $value = $mContactValue->record->where('id_contact', $contact->id)->where('type', 'email')->first();
    return $value?->value ?? '';
  }

  private function send(string $emailTo, string $subject, string $body, int $kind, int $idWorker, int $idCertificate, int $idCustomer): void
  {
    /** @var ExpiryNotification */
    $mExpiryNotification = $this->getModel(ExpiryNotification::class);

    $isDelivered = true;
    $error = '';

    if (empty($emailTo)) {
      $isDelivered = false;
      $error = 'No email address available for recipient.';
    } else {
      try {
        $this->getService(Mailer::class)->sendWithAttachment($emailTo, $subject, nl2br(htmlspecialchars($body)));
      } catch (\Throwable $e) {
        $isDelivered = false;
        $error = $e->getMessage();
      }
    }

    $mExpiryNotification->record->recordCreate([
      'id_worker' => $idWorker ?: null,
      'id_certificate' => $idCertificate ?: null,
      'id_customer' => $idCustomer ?: null,
      'kind' => $kind,
      'sent_on' => date('Y-m-d H:i:s'),
      'email_to' => $emailTo,
      'is_delivered' => $isDelivered,
      'error' => $error,
    ]);

    if (!$isDelivered) {
      $adminUserIds = $this->getService(\Hubleto\App\Custom\Trainings\AdminUsers::class)->getAdminUserIds();
      $notificationSender = $this->getService(\Hubleto\App\Community\Notifications\Sender::class);
      foreach ($adminUserIds as $idUser) {
        $notificationSender->send(
          0,
          [],
          ExpiryNotification::class,
          0,
          $idUser,
          $this->translate('Retraining reminder could not be delivered'),
          $error . ' (' . $emailTo . ')'
        );
      }
    }
  }
}
