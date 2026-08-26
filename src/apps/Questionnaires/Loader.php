<?php

namespace Hubleto\App\Custom\Questionnaires;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^questionnaires(\/(?<recordId>\d+))?\/?$/' => Controllers\Questionnaires::class,
    ]);

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'questionnaires', $this->translate('Questionnaires'), 'fas fa-clipboard-question');
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Questionnaire::class)->upgradeSchema();
    }
  }

  public function generateDemoData(): void
  {
  }

}
