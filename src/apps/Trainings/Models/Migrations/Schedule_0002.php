<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

/** The lecturer is not tracked on a training date any more. */
class Schedule_0002 extends \HubletoProject\Dependency\Migration
{

  public function upgradeSchema(): void
  {
    $this->dropColumns('schedules', ['id_lecturer']);
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
