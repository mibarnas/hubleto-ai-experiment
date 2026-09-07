<?php

namespace Hubleto\App\Custom\Workers\Controllers;

class Settings extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'workers', 'content' => $this->translate('Workers') ],
      [ 'url' => 'settings/workers', 'content' => $this->translate('Settings') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Workers/Settings.twig');
  }

}
