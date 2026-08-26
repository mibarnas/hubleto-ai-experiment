<?php

namespace Hubleto\App\Custom\Workers\Models\Migrations;

use Hubleto\Framework\Migration;

class ExpiryNotification_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `expiry_notifications`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `expiry_notifications` (
 `id` int(8) primary key auto_increment,
 `id_worker` int(8) NULL default NULL,
 `id_certificate` int(8) NULL default NULL,
 `id_customer` int(8) NULL default NULL,
 `kind` int(255) ,
 `sent_on` datetime ,
 `email_to` varchar(255) ,
 `is_delivered` int(1) ,
 `error` text ,
 index `id` (`id`),
 index `id_worker` (`id_worker`),
 index `id_certificate` (`id_certificate`),
 index `id_customer` (`id_customer`),
 index `kind` (`kind`),
 index `sent_on` (`sent_on`),
 index `is_delivered` (`is_delivered`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `expiry_notifications`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `expiry_notifications`
          ADD CONSTRAINT `fk_fe45abcdc3999d50ac6bfda636adb203`
          FOREIGN KEY (`id_worker`)
          REFERENCES `workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `expiry_notifications`
          ADD CONSTRAINT `fk_20d15169e505d1ae552f6beb9f598292`
          FOREIGN KEY (`id_customer`)
          REFERENCES `customers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `expiry_notifications`
          DROP FOREIGN KEY `fk_fe45abcdc3999d50ac6bfda636adb203`; ALTER TABLE `expiry_notifications`
          DROP FOREIGN KEY `fk_20d15169e505d1ae552f6beb9f598292`;");
  }
}