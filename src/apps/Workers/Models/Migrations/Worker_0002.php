<?php

namespace Hubleto\App\Custom\Workers\Models\Migrations;

/**
 * Drops the denormalised retraining columns.
 *
 * The next retraining date and the training to retake are now derived from the
 * worker's certificates at read time, so a stored copy could only go stale.
 */
class Worker_0002 extends \HubletoProject\Dependency\Migration
{

  public function upgradeSchema(): void
  {
    $this->dropColumns('workers', [
      'date_next_retraining',
      'id_next_retraining_training',
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
