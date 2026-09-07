<?php

namespace Hubleto\App\Custom\Questionnaires\Controllers;

class Questionnaires extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'questionnaires', 'content' => $this->translate('Questionnaires') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Questionnaires/Questionnaires.twig');
  }
}
