<?php

namespace Hubleto\App\Custom\Workers\Controllers;

class Workers extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'workers', 'content' => $this->translate('Workers') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Workers/Workers.twig');
  }

}
