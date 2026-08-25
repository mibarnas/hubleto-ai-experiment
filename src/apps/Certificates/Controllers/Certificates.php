<?php

namespace Hubleto\App\Custom\Certificates\Controllers;

class Certificates extends \Hubleto\Erp\Controller
{

  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'certificates', 'content' => $this->translate('Certificates') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:Custom:Certificates/Certificates.twig');
  }

}
