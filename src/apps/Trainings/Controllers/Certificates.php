<?php

namespace Hubleto\App\Custom\Trainings\Controllers;

class Certificates extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'trainings/certificates', 'content' => $this->translate('Certificates') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Trainings/Certificates.twig');
  }
}
