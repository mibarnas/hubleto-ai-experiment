<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

/**
 * Adds `shared_with`, completing the row-level ACL trio
 * (id_owner / id_manager / shared_with) that Erp\Model::getPermissions() reads.
 */
class TrainingOrder_0002 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("alter table `training_orders` add column `shared_with` text NULL default NULL;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("alter table `training_orders` drop column `shared_with`;");
  }

  public function upgradeForeignKeys(): void
  {
    // no foreign keys introduced by this migration
  }

  public function downgradeForeignKeys(): void
  {
    // no foreign keys introduced by this migration
  }

}
