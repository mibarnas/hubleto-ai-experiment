<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class Training_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `trainings`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `trainings` (
 `id` int(8) primary key auto_increment,
 `name` varchar(255) ,
 `training_number` varchar(255) ,
 `price_per_person` decimal(14, 2) ,
 `id_currency` int(8) NULL default NULL,
 `retraining_interval_years` int(255) ,
 `id_company` int(8) NULL default NULL,
 `certificate_template` varchar(255) ,
 `certificate_template_params` text ,
 `ruvz_name` varchar(255) ,
 `ruvz_certificate_number` varchar(255) ,
 `ruvz_date_issued` date ,
 `is_active` int(1) ,
 `description` text ,
 `id_owner` int(8) NULL default NULL,
 `id_manager` int(8) NULL default NULL,
 index `id` (`id`),
 index `id_currency` (`id_currency`),
 index `retraining_interval_years` (`retraining_interval_years`),
 index `id_company` (`id_company`),
 index `ruvz_date_issued` (`ruvz_date_issued`),
 index `is_active` (`is_active`),
 index `id_owner` (`id_owner`),
 index `id_manager` (`id_manager`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `trainings`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `trainings`
          ADD CONSTRAINT `fk_0a44a3c592fe54aca1eafe3eb4b4c476`
          FOREIGN KEY (`id_currency`)
          REFERENCES `currencies` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `trainings`
          ADD CONSTRAINT `fk_bc966702113505bffd335c0353407712`
          FOREIGN KEY (`id_company`)
          REFERENCES `companies` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `trainings`
          ADD CONSTRAINT `fk_bfb936af5cf6925e5241c5e2fef0d3a1`
          FOREIGN KEY (`id_owner`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `trainings`
          ADD CONSTRAINT `fk_06dacaa323a455103d945fe5b22bacdf`
          FOREIGN KEY (`id_manager`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `trainings`
          DROP FOREIGN KEY `fk_0a44a3c592fe54aca1eafe3eb4b4c476`; ALTER TABLE `trainings`
          DROP FOREIGN KEY `fk_bc966702113505bffd335c0353407712`; ALTER TABLE `trainings`
          DROP FOREIGN KEY `fk_bfb936af5cf6925e5241c5e2fef0d3a1`; ALTER TABLE `trainings`
          DROP FOREIGN KEY `fk_06dacaa323a455103d945fe5b22bacdf`;");
  }
}