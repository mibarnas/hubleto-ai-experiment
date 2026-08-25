<?php

namespace Hubleto\App\Custom\Certificates\Models\Migrations;

use Hubleto\Framework\Migration;

class Certificate_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_certificates`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `training_certificates` (
 `id` int(8) primary key auto_increment,
 `id_applicant` int(8) NULL default NULL,
 `id_worker` int(8) NULL default NULL,
 `id_training` int(8) NULL default NULL,
 `certificate_number` varchar(255) ,
 `date_created` date ,
 `date_valid_until` date ,
 `ruvz_name` varchar(255) ,
 `ruvz_certificate_number` varchar(255) ,
 `ruvz_date_issued` date ,
 `id_document` int(8) NULL default NULL,
 `file` varchar(255) ,
 `sent_to_applicant_on` datetime ,
 index `id` (`id`),
 index `id_applicant` (`id_applicant`),
 index `id_worker` (`id_worker`),
 index `id_training` (`id_training`),
 index `date_created` (`date_created`),
 index `date_valid_until` (`date_valid_until`),
 index `ruvz_date_issued` (`ruvz_date_issued`),
 index `id_document` (`id_document`),
 index `sent_to_applicant_on` (`sent_to_applicant_on`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `training_certificates`
              add constraint `id_applicant` unique (`id_applicant` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_certificates`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_certificates`
          ADD CONSTRAINT `fk_eb71c5f8edb12aeccb5c8c33d8bac31e`
          FOREIGN KEY (`id_applicant`)
          REFERENCES `training_applicants` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_certificates`
          ADD CONSTRAINT `fk_ac74f7adf15721435765bd6c3dd4142f`
          FOREIGN KEY (`id_worker`)
          REFERENCES `training_workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_certificates`
          ADD CONSTRAINT `fk_2c893eff3a5d8dcd2697c15369eeb2b4`
          FOREIGN KEY (`id_training`)
          REFERENCES `trainings` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_certificates`
          ADD CONSTRAINT `fk_d30b5673e2864fc92cb66255a5a1ec23`
          FOREIGN KEY (`id_document`)
          REFERENCES `documents` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_certificates`
          DROP FOREIGN KEY `fk_eb71c5f8edb12aeccb5c8c33d8bac31e`; ALTER TABLE `training_certificates`
          DROP FOREIGN KEY `fk_ac74f7adf15721435765bd6c3dd4142f`; ALTER TABLE `training_certificates`
          DROP FOREIGN KEY `fk_2c893eff3a5d8dcd2697c15369eeb2b4`; ALTER TABLE `training_certificates`
          DROP FOREIGN KEY `fk_d30b5673e2864fc92cb66255a5a1ec23`;");
  }
}