<?php

namespace Hubleto\App\Custom\Workers;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      // Serves upload/ (blocked from direct access by upload/.htaccess) to signed-in users.
      '/^file\/(?<path>.+)$/' => Controllers\UploadedFile::class,

      '/^workers\/add\/?$/' => ['controller' => Controllers\Workers::class, 'vars' => ['recordId' => -1]],
      '/^workers(\/(?<recordId>\d+))?\/?$/' => Controllers\Workers::class,
      '/^settings\/workers\/?$/' => Controllers\Settings::class,
    ]);

    $settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Workers'),
      'icon' => 'fas fa-user-tie',
      'url' => 'settings/workers',
    ]);

    $this->cronManager()->addCron(Crons\RetrainingReminders::class);
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Worker::class)->upgradeSchema();
      $this->getModel(Models\ExpiryNotification::class)->upgradeSchema();
    }
  }

  public function generateDemoData(): void
  {
    // Create any demo data to promote your app.
  }

}
