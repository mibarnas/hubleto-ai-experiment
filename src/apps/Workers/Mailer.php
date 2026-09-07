<?php

namespace Hubleto\App\Custom\Workers;

/**
 * Thin wrapper around the Mail app's queue, used wherever this project needs
 * to send an email with attachments (EmailProvider has no attachment support).
 * Resolves the first configured mails_accounts row -- AlgoCorp needs exactly
 * one SMTP account set up under Mail > Accounts for any of this to work.
 */
class Mailer extends \Hubleto\Erp\Core
{
  public string $translationContext = 'hubleto-app-custom-workers-loader';
  public string $translationContextInner = 'Mailer';

  /**
   * @param string|string[] $to One address, an array of addresses, or a list
   *   separated by `;` or `,`. The Mail app splits `to` on commas before
   *   handing it to PHPMailer, so everything is normalised to that form and a
   *   single send reaches every recipient.
   * @param array $attachments [['name' => ..., 'file' => <path under upload/>], ...]
   */
  public function sendWithAttachment(string|array $to, string $subject, string $bodyHtml, array $attachments = []): int
  {
    $recipients = self::normalizeRecipients($to);
    if (empty($recipients)) return 0;

    /** @var \Hubleto\App\Community\Mail\Models\Account */
    $mAccount = $this->getModel(\Hubleto\App\Community\Mail\Models\Account::class);
    $account = $mAccount->record->orderBy('id')->first();
    if (!$account) {
      throw new \Exception($this->translate('No mail account is configured. Create one under Mail > Accounts before sending emails.'));
    }

    /** @var \Hubleto\App\Community\Mail\Models\Mail */
    $mMail = $this->getModel(\Hubleto\App\Community\Mail\Models\Mail::class);
    return $mMail->createAndSend([
      'id_account' => $account->id,
      'to' => implode(',', $recipients),
      'subject' => $subject,
      'body_html' => $bodyHtml,
      'body_text' => strip_tags($bodyHtml),
    ], $attachments);
  }

  /**
   * Accepts `a@b.sk;c@d.sk`, `a@b.sk, c@d.sk` or an array, and returns the
   * unique, valid addresses in it.
   *
   * @param string|string[] $to
   * @return string[]
   */
  public static function normalizeRecipients(string|array $to): array
  {
    $candidates = is_array($to) ? $to : preg_split('/[;,]/', $to);

    $recipients = [];
    foreach ((array) $candidates as $candidate) {
      $candidate = strtolower(trim((string) $candidate));
      if ($candidate === '') continue;
      if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) continue;
      $recipients[$candidate] = true;
    }

    return array_keys($recipients);
  }
}
