<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Orders extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
      [ 'url' => 'trainings/orders', 'content' => $this->translate('Orders') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Orders.twig');
  }

}
