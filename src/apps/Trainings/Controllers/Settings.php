<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Settings extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
      [ 'url' => 'settings/trainings', 'content' => $this->translate('Settings') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Settings.twig');
  }

}
