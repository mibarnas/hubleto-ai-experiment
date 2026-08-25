<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class TrainingDate_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_dates`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `training_dates` (
 `id` int(8) primary key auto_increment,
 `id_training` int(8) NULL default NULL,
 `datetime_start` datetime ,
 `datetime_end` datetime ,
 `teams_link` varchar(255) ,
 `id_lecturer` int(8) NULL default NULL,
 `capacity` int(255) ,
 `note` text ,
 index `id` (`id`),
 index `id_training` (`id_training`),
 index `datetime_start` (`datetime_start`),
 index `datetime_end` (`datetime_end`),
 index `id_lecturer` (`id_lecturer`),
 index `capacity` (`capacity`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_dates`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_dates`
          ADD CONSTRAINT `fk_8d38c5ad34eb893bb85a8c1c0a154c13`
          FOREIGN KEY (`id_training`)
          REFERENCES `trainings` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_dates`
          ADD CONSTRAINT `fk_f7f4315c3d403dc873aee008a000b4cc`
          FOREIGN KEY (`id_lecturer`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_dates`
          DROP FOREIGN KEY `fk_8d38c5ad34eb893bb85a8c1c0a154c13`; ALTER TABLE `training_dates`
          DROP FOREIGN KEY `fk_f7f4315c3d403dc873aee008a000b4cc`;");
  }
}