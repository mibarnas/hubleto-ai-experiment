<?php

namespace Hubleto\App\Custom\TrainingOrders\Models\Migrations;

/**
 * Drops the ownership block and the denormalised pricing columns.
 *
 * Price per person now comes from the booked training, and the head count and
 * total are derived from the order's attendees, so keeping copies in the record
 * could only let them drift.
 */
class Order_0002 extends \HubletoProject\Dependency\Migration
{

  public function upgradeSchema(): void
  {
    $this->dropColumns('training_orders', [
      'price',
      'number_of_workers',
      'total_price',
      'id_owner',
      'id_manager',
      'shared_with',
    ]);
  }

  public function downgradeSchema(): void
  {
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
