<?php

namespace Hubleto\App\Custom\Workers\Models\Migrations;

use Hubleto\Framework\Migration;

class Worker_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `workers`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `workers` (
 `id` int(8) primary key auto_increment,
 `id_customer` int(8) NULL default NULL,
 `title_before` varchar(255) ,
 `first_name` varchar(255) ,
 `last_name` varchar(255) ,
 `title_after` varchar(255) ,
 `email` varchar(255) ,
 `phone` varchar(255) ,
 `gender` int(255) ,
 `birth_number` varchar(255) ,
 `address` varchar(255) ,
 `city` varchar(255) ,
 `zip` varchar(255) ,
 `id_country` int(8) NULL default NULL,
 `workplace_name` varchar(255) ,
 `workplace_address` varchar(255) ,
 `workplace_city` varchar(255) ,
 `workplace_zip` varchar(255) ,
 `date_next_retraining` date ,
 `id_next_retraining_training` int(8) NULL default NULL,
 `note` text ,
 `id_owner` int(8) NULL default NULL,
 `id_manager` int(8) NULL default NULL,
 index `id` (`id`),
 index `id_customer` (`id_customer`),
 index `gender` (`gender`),
 index `id_country` (`id_country`),
 index `date_next_retraining` (`date_next_retraining`),
 index `id_next_retraining_training` (`id_next_retraining_training`),
 index `id_owner` (`id_owner`),
 index `id_manager` (`id_manager`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `workers`
              add constraint `email` unique (`email` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `workers`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `workers`
          ADD CONSTRAINT `fk_843c4a9d34aca03d98dfa2ff4b0fba80`
          FOREIGN KEY (`id_customer`)
          REFERENCES `customers` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `workers`
          ADD CONSTRAINT `fk_5ae782d89f8c9a572f0f4226bc51723a`
          FOREIGN KEY (`id_country`)
          REFERENCES `countries` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `workers`
          ADD CONSTRAINT `fk_b53efd5647652fd19dc36a14c4ad9a76`
          FOREIGN KEY (`id_owner`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT; ALTER TABLE `workers`
          ADD CONSTRAINT `fk_c5813e70a9e8bb51f0cacc9525af2e78`
          FOREIGN KEY (`id_manager`)
          REFERENCES `users` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `workers`
          DROP FOREIGN KEY `fk_843c4a9d34aca03d98dfa2ff4b0fba80`; ALTER TABLE `workers`
          DROP FOREIGN KEY `fk_5ae782d89f8c9a572f0f4226bc51723a`; ALTER TABLE `workers`
          DROP FOREIGN KEY `fk_b53efd5647652fd19dc36a14c4ad9a76`; ALTER TABLE `workers`
          DROP FOREIGN KEY `fk_c5813e70a9e8bb51f0cacc9525af2e78`;");
  }
}