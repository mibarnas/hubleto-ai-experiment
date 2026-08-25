<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Applicants extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings', 'content' => $this->translate('Trainings') ],
      [ 'url' => 'trainings/applicants', 'content' => $this->translate('Applicants') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Applicants.twig');
  }

}
