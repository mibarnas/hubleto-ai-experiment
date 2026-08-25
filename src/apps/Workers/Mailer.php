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
  public function sendWithAttachment(string $to, string $subject, string $bodyHtml, array $attachments = []): int
  {
    if (empty($to)) return 0;

    /** @var \Hubleto\App\Community\Mail\Models\Account */
    $mAccount = $this->getModel(\Hubleto\App\Community\Mail\Models\Account::class);
    $account = $mAccount->record->orderBy('id')->first();
    if (!$account) {
      throw new \Exception('No mail account is configured. Create one under Mail > Accounts before sending emails.');
    }

    /** @var \Hubleto\App\Community\Mail\Models\Mail */
    $mMail = $this->getModel(\Hubleto\App\Community\Mail\Models\Mail::class);
    return $mMail->createAndSend([
      'id_account' => $account->id,
      'to' => $to,
      'subject' => $subject,
      'body_html' => $bodyHtml,
      'body_text' => strip_tags($bodyHtml),
    ], $attachments);
  }
}
