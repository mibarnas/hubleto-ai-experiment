<?php

namespace Hubleto\App\Custom\Questionnaires\Controllers;

class Questionnaires extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Questionnaires/Questionnaires.twig');
  }
}
