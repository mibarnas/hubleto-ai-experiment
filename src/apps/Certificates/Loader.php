<?php

namespace Hubleto\App\Custom\Certificates;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^certificates\/api\/generate\/?$/' => Controllers\Api\Generate::class,
      '/^certificates(\/(?<recordId>\d+))?\/?$/' => Controllers\Certificates::class,
      '/^certificates\/download\/?$/' => Controllers\Download::class,
      '/^settings\/certificates\/?$/' => Controllers\Settings::class,
    ]);

    $settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Certificates'),
      'icon' => 'fas fa-certificate',
      'url' => 'settings/certificates',
    ]);

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'certificates', $this->translate('Certificates'), 'fas fa-certificate');
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Certificate::class)->upgradeSchema();
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
