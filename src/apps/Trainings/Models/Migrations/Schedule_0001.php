<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class Schedule_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `schedules`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `schedules` (
 `id` int(8) primary key auto_increment,
 `id_training` int(8) NULL default NULL,
 `date_start` datetime ,
 `date_end` datetime ,
 `meeting_link` varchar(255) ,
 `id_lecturer` int(8) NULL default NULL,
 `capacity` int(255) ,
 `note` text ,
 index `id` (`id`),
 index `id_training` (`id_training`),
 index `date_start` (`date_start`),
 index `date_end` (`date_end`),
 index `id_lecturer` (`id_lecturer`),
 index `capacity` (`capacity`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `schedules`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `schedules`
          ADD CONSTRAINT `fk_2fa6cac415c1469211de6e22e6806163`
          FOREIGN KEY (`id_training`)
          REFERENCES `trainings` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `schedules`
          ADD CONSTRAINT `fk_63913557027c95235fba90605e79d070`
          FOREIGN KEY (`id_lecturer`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `schedules`
          DROP FOREIGN KEY `fk_2fa6cac415c1469211de6e22e6806163`; ALTER TABLE `schedules`
          DROP FOREIGN KEY `fk_63913557027c95235fba90605e79d070`;");
  }
}