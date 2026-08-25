<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class Applicant_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_applicants`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `training_applicants` (
 `id` int(8) primary key auto_increment,
 `id_training_date` int(8) NULL default NULL,
 `id_worker` int(8) NULL default NULL,
 `id_order` int(8) NULL default NULL,
 `date_registered` date ,
 `is_completed` int(1) ,
 `previous_certificate_file` varchar(255) ,
 `questionnaire_token` varchar(255) ,
 `questionnaire_sent_on` datetime ,
 `questionnaire_filled_on` datetime ,
 `catalog_token` varchar(255) ,
 `catalog_filled_on` datetime ,
 `education_level` int(255) ,
 `financing_type` int(255) ,
 `meeting_link_sent_on` datetime ,
 index `id` (`id`),
 index `id_training_date` (`id_training_date`),
 index `id_worker` (`id_worker`),
 index `id_order` (`id_order`),
 index `date_registered` (`date_registered`),
 index `is_completed` (`is_completed`),
 index `questionnaire_sent_on` (`questionnaire_sent_on`),
 index `questionnaire_filled_on` (`questionnaire_filled_on`),
 index `catalog_filled_on` (`catalog_filled_on`),
 index `education_level` (`education_level`),
 index `financing_type` (`financing_type`),
 index `meeting_link_sent_on` (`meeting_link_sent_on`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `training_applicants`
              add constraint `questionnaire_token` unique (`questionnaire_token` asc)
            ; 
              alter table `training_applicants`
              add constraint `catalog_token` unique (`catalog_token` asc)
            ; 
              alter table `training_applicants`
              add constraint `id_training_date__id_worker` unique (`id_training_date` asc, `id_worker` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_applicants`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_applicants`
          ADD CONSTRAINT `fk_df25880e8cd55f8f74e8adeb9d6cd7d6`
          FOREIGN KEY (`id_training_date`)
          REFERENCES `training_dates` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_applicants`
          ADD CONSTRAINT `fk_965faa1585389853c96bfdc62798cc01`
          FOREIGN KEY (`id_worker`)
          REFERENCES `training_workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_applicants`
          ADD CONSTRAINT `fk_a2ee974f9dbc8d9ea991cc7ec52cdcc7`
          FOREIGN KEY (`id_order`)
          REFERENCES `training_orders` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_applicants`
          DROP FOREIGN KEY `fk_df25880e8cd55f8f74e8adeb9d6cd7d6`; ALTER TABLE `training_applicants`
          DROP FOREIGN KEY `fk_965faa1585389853c96bfdc62798cc01`; ALTER TABLE `training_applicants`
          DROP FOREIGN KEY `fk_a2ee974f9dbc8d9ea991cc7ec52cdcc7`;");
  }
}