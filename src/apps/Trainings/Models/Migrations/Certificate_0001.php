<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class Certificate_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `certificates`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `certificates` (
 `id` int(8) primary key auto_increment,
 `id_worker` int(8) NULL default NULL,
 `id_training` int(8) NULL default NULL,
 `internal_number` varchar(255) ,
 `date_internal_validity` date ,
 `external_number` varchar(255) ,
 `date_external_validity` date ,
 `name_validator` varchar(255) ,
 `date_expiration` date ,
 `file` varchar(255) ,
 `file_docx` varchar(255) ,
 `id_document` int(8) NULL default NULL,
 `date_sent` datetime ,
 index `id` (`id`),
 index `id_worker` (`id_worker`),
 index `id_training` (`id_training`),
 index `date_internal_validity` (`date_internal_validity`),
 index `date_external_validity` (`date_external_validity`),
 index `date_expiration` (`date_expiration`),
 index `id_document` (`id_document`),
 index `date_sent` (`date_sent`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `certificates`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `certificates`
          ADD CONSTRAINT `fk_efb1cf3a99227d0b8c1f500536c35a5a`
          FOREIGN KEY (`id_worker`)
          REFERENCES `workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `certificates`
          ADD CONSTRAINT `fk_0f6b98a974a3bb89229ee104a54d0e2b`
          FOREIGN KEY (`id_training`)
          REFERENCES `trainings` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `certificates`
          ADD CONSTRAINT `fk_748b72b2c68fb3d228268d35d97861b1`
          FOREIGN KEY (`id_document`)
          REFERENCES `documents` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `certificates`
          DROP FOREIGN KEY `fk_efb1cf3a99227d0b8c1f500536c35a5a`; ALTER TABLE `certificates`
          DROP FOREIGN KEY `fk_0f6b98a974a3bb89229ee104a54d0e2b`; ALTER TABLE `certificates`
          DROP FOREIGN KEY `fk_748b72b2c68fb3d228268d35d97861b1`;");
  }
}