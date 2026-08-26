<?php

namespace Hubleto\App\Custom\Orders;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^orders\/api\/import-order-workers\/?$/' => Controllers\Api\ImportOrderWorkers::class,
      '/^orders\/api\/recalculate-order\/?$/' => Controllers\Api\RecalculateOrder::class,

      '/^orders\/add\/?$/' => ['controller' => Controllers\Orders::class, 'vars' => ['recordId' => -1]],
      '/^orders(\/(?<recordId>\d+))?\/?$/' => Controllers\Orders::class,
    ]);

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'orders', $this->translate('Training orders'), 'fas fa-file-invoice');
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Order::class)->upgradeSchema();
    }
  }

  public function generateDemoData(): void
  {
  }

}
