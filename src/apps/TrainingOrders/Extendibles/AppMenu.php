<?php

namespace Hubleto\App\Custom\TrainingOrders\Extendibles;

class AppMenu extends \Hubleto\Framework\Extendible
{
  public function getItems(): array
  {
    return [
      [
        'app' => $this->app,
        'url' => 'training-orders',
        'title' => $this->app->translate('Training orders'),
        'icon' => 'fas fa-file-invoice',
      ],
    ];
  }
}
