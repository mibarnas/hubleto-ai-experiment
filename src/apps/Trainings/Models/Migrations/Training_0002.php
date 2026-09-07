<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

/**
 * Removes what a training must no longer carry: the ownership block (owner,
 * manager, shared_with), the currency, the active flag, and the accreditation
 * defaults -- accreditation is a property of the issued certificate, not of the
 * training.
 */
class Training_0002 extends \HubletoProject\Dependency\Migration
{

  public function upgradeSchema(): void
  {
    $this->dropColumns('trainings', [
      'id_currency',
      'is_active',
      'name_validator',
      'external_number',
      'date_external_issued',
      'id_owner',
      'id_manager',
      'shared_with',
    ]);

    // The stored placeholder list changed shape: a flat array of `<name>`
    // strings became the `<< name >>` analysis. The old value is discarded and
    // rebuilt the next time the template is uploaded or re-checked.
    $this->db->execute("update `trainings` set `template_params` = null;");
  }

  public function downgradeSchema(): void
  {
    // Purely destructive -- the dropped values cannot be recovered.
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
