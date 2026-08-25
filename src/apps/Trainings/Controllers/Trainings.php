<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Trainings extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Trainings.twig');
  }

}
