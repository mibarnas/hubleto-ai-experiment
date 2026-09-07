<?php

namespace Hubleto\App\Custom\TrainingOrders\Controllers;

class Orders extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'training-orders', 'content' => $this->translate('Training orders') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:TrainingOrders/Orders.twig');
  }

}
