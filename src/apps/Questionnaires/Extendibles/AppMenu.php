<?php

namespace Hubleto\App\Custom\Questionnaires\Extendibles;

class AppMenu extends \Hubleto\Framework\Extendible
{
  public function getItems(): array
  {
    return [
      [
        'app' => $this->app,
        'url' => 'questionnaires',
        'title' => $this->app->translate('Questionnaires'),
        'icon' => 'fas fa-clipboard-question',
      ],
    ];
  }
}
