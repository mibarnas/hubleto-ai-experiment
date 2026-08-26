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

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'workers', $this->translate('Workers'), 'fas fa-user-tie');

    $this->cronManager()->addCron(Crons\RetrainingReminders::class);
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Worker::class)->upgradeSchema();
      $this->getModel(Models\ExpiryNotification::class)->upgradeSchema();
    }
    if ($round == 2) {
      // do something in the 2nd round, if required
    }
    if ($round == 3) {
      // do something in the 3rd round, if required
    }
  }

  public function generateDemoData(): void
  {
    // Create any demo data to promote your app.
  }

}
