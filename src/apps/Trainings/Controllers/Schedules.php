<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Schedules extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
      [ 'url' => 'trainings/schedules', 'content' => $this->translate('Schedules') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Schedules.twig');
  }

}
