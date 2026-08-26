<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Attendees extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
      [ 'url' => 'trainings/attendees', 'content' => $this->translate('Attendees') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Attendees.twig');
  }

}
