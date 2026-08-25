<?php

namespace Hubleto\App\Custom\Workers\Crons;

class RetrainingReminders extends \Hubleto\Erp\Cron
{
  public string $schedulingPattern = '0 6 * * *';

  public function run(): void
  {
    $this->logger()->info('RetrainingReminders cron started.');
    $this->getService(\Hubleto\App\Custom\Workers\ExpiryNotifier::class)->run(new \DateTimeImmutable());
  }
}
