<?php

namespace Hubleto\App\Custom\TrainingOrders;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      // Note: Controllers\Api\PlaceOrder -- the endpoint the public website
      // posts its orders to -- deliberately has no route of its own. It is
      // invoked through the community Api app's gateway (`POST api/call` with
      // an API key), which authenticates the caller, checks its permissions and
      // records the usage. A route here would instead require a user session.

      '/^training-orders\/api\/import-order-workers\/?$/' => Controllers\Api\ImportOrderWorkers::class,
      '/^training-orders\/api\/add-order-workers\/?$/' => Controllers\Api\AddOrderWorkers::class,

      '/^training-orders\/workers-template\/?$/' => Controllers\DownloadWorkersTemplate::class,

      '/^training-orders\/add\/?$/' => ['controller' => Controllers\Orders::class, 'vars' => ['recordId' => -1]],
      '/^training-orders(\/(?<recordId>\d+))?\/?$/' => Controllers\Orders::class,
    ]);
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
