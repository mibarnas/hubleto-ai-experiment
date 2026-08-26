<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class Attendee_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `attendees`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `attendees` (
 `id` int(8) primary key auto_increment,
 `id_schedule` int(8) NULL default NULL,
 `id_worker` int(8) NULL default NULL,
 `id_order` int(8) NULL default NULL,
 `id_certificate` int(8) NULL default NULL,
 `id_questionnaire` int(8) NULL default NULL,
 `is_passed` int(1) ,
 `date_registered` date ,
 `file_last_certificate` varchar(255) ,
 `questionnaire_token` varchar(255) ,
 `url_questionnaire` varchar(255) ,
 `date_questionnaire_sent` datetime ,
 `date_questionnaire_filled` datetime ,
 `catalog_token` varchar(255) ,
 `date_catalog_filled` datetime ,
 `education_level` int(255) ,
 `financing_type` int(255) ,
 `date_meeting_link_sent` datetime ,
 index `id` (`id`),
 index `id_schedule` (`id_schedule`),
 index `id_worker` (`id_worker`),
 index `id_order` (`id_order`),
 index `id_certificate` (`id_certificate`),
 index `id_questionnaire` (`id_questionnaire`),
 index `is_passed` (`is_passed`),
 index `date_registered` (`date_registered`),
 index `date_questionnaire_sent` (`date_questionnaire_sent`),
 index `date_questionnaire_filled` (`date_questionnaire_filled`),
 index `date_catalog_filled` (`date_catalog_filled`),
 index `education_level` (`education_level`),
 index `financing_type` (`financing_type`),
 index `date_meeting_link_sent` (`date_meeting_link_sent`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `attendees`
              add constraint `questionnaire_token` unique (`questionnaire_token` asc)
            ; 
              alter table `attendees`
              add constraint `catalog_token` unique (`catalog_token` asc)
            ; 
              alter table `attendees`
              add constraint `id_schedule__id_worker` unique (`id_schedule` asc, `id_worker` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `attendees`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `attendees`
          ADD CONSTRAINT `fk_455f159f408425feca0fdca84f588c77`
          FOREIGN KEY (`id_schedule`)
          REFERENCES `schedules` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `attendees`
          ADD CONSTRAINT `fk_6f1e59c121f69d85a248fc5384497272`
          FOREIGN KEY (`id_worker`)
          REFERENCES `workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `attendees`
          ADD CONSTRAINT `fk_37885d4f196b7d84f28164ce550a9c3d`
          FOREIGN KEY (`id_certificate`)
          REFERENCES `certificates` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `attendees`
          ADD CONSTRAINT `fk_c61a5150c0bf4477a1e6ed078485df6f`
          FOREIGN KEY (`id_questionnaire`)
          REFERENCES `questionnaires` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `attendees`
          DROP FOREIGN KEY `fk_455f159f408425feca0fdca84f588c77`; ALTER TABLE `attendees`
          DROP FOREIGN KEY `fk_6f1e59c121f69d85a248fc5384497272`; ALTER TABLE `attendees`
          DROP FOREIGN KEY `fk_37885d4f196b7d84f28164ce550a9c3d`; ALTER TABLE `attendees`
          DROP FOREIGN KEY `fk_c61a5150c0bf4477a1e6ed078485df6f`;");
  }
}