<?php

namespace Hubleto\App\Custom\Questionnaires\Models\Migrations;

/**
 * Adds the missing link back to the attendee who filled the questionnaire in.
 *
 * Existing rows are matched through `attendees.id_questionnaire`, which is the
 * only place the pairing was recorded before.
 */
class Questionnaire_0002 extends \HubletoProject\Dependency\Migration
{

  public function upgradeSchema(): void
  {
    $this->addColumn('questionnaires', 'id_attendee', 'int(8) null default null');
    $this->addIndex('questionnaires', 'id_attendee', '`id_attendee`');

    $this->db->execute("
      update `questionnaires` `q`
      inner join `attendees` `a` on `a`.`id_questionnaire` = `q`.`id`
      set `q`.`id_attendee` = `a`.`id`
      where `q`.`id_attendee` is null
    ");
  }

  public function downgradeSchema(): void
  {
    $this->dropColumns('questionnaires', ['id_attendee']);
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
