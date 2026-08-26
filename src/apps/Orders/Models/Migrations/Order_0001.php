<?php

namespace Hubleto\App\Custom\Orders\Models\Migrations;

use Hubleto\Framework\Migration;

class Order_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_orders`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `training_orders` (
 `id` int(8) primary key auto_increment,
 `identifier` varchar(255) ,
 `order_type` int(255) ,
 `id_customer` int(8) NULL default NULL,
 `id_contact` int(8) NULL default NULL,
 `id_worker` int(8) NULL default NULL,
 `id_schedule` int(8) NULL default NULL,
 `price` decimal(14, 2) ,
 `number_of_workers` int(255) ,
 `total_price` decimal(14, 2) ,
 `id_currency` int(8) NULL default NULL,
 `date_ordered` date ,
 `date_paid` date ,
 `file_workers` varchar(255) ,
 `note` text ,
 `id_owner` int(8) NULL default NULL,
 `id_manager` int(8) NULL default NULL,
 `shared_with` text ,
 index `id` (`id`),
 index `order_type` (`order_type`),
 index `id_customer` (`id_customer`),
 index `id_contact` (`id_contact`),
 index `id_worker` (`id_worker`),
 index `id_schedule` (`id_schedule`),
 index `number_of_workers` (`number_of_workers`),
 index `id_currency` (`id_currency`),
 index `date_ordered` (`date_ordered`),
 index `date_paid` (`date_paid`),
 index `id_owner` (`id_owner`),
 index `id_manager` (`id_manager`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_orders`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_c9fb57cc7814f3ed7b04fd9a8af1e235`
          FOREIGN KEY (`id_customer`)
          REFERENCES `customers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_834fac2105252bcee274cf4e04efe3a7`
          FOREIGN KEY (`id_contact`)
          REFERENCES `contacts` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_522eb6b5f53982c076f9571a550f928c`
          FOREIGN KEY (`id_worker`)
          REFERENCES `workers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_c0943cab1fabbb920834d55c6d0ad2a6`
          FOREIGN KEY (`id_schedule`)
          REFERENCES `schedules` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_9b73efb2453b3bc61d2568bffc81c64f`
          FOREIGN KEY (`id_currency`)
          REFERENCES `currencies` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_610c43cbc57388151c522e6fcb5f4a7e`
          FOREIGN KEY (`id_owner`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `training_orders`
          ADD CONSTRAINT `fk_744d19e67a1d47a044371205c06cbf77`
          FOREIGN KEY (`id_manager`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_c9fb57cc7814f3ed7b04fd9a8af1e235`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_834fac2105252bcee274cf4e04efe3a7`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_522eb6b5f53982c076f9571a550f928c`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_c0943cab1fabbb920834d55c6d0ad2a6`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_9b73efb2453b3bc61d2568bffc81c64f`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_610c43cbc57388151c522e6fcb5f4a7e`; ALTER TABLE `training_orders`
          DROP FOREIGN KEY `fk_744d19e67a1d47a044371205c06cbf77`;");
  }
}