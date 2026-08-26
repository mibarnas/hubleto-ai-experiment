<?php

namespace Hubleto\App\Custom\Questionnaires\Models\Migrations;

use Hubleto\Framework\Migration;

class Questionnaire_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `questionnaires`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `questionnaires` (
 `id` int(8) primary key auto_increment,
 `date_filled` datetime ,
 `q_content_clear` int(255) ,
 `q_met_expectations` int(255) ,
 `q_knowledge_useful` int(255) ,
 `q_lecturer_expert` int(255) ,
 `q_lecturer_communication` int(255) ,
 `q_lecturer_environment` int(255) ,
 `q_organization` int(255) ,
 `q_tech_support` int(255) ,
 `q_information` int(255) ,
 `q_overall_satisfaction` int(255) ,
 `q_would_recommend` int(255) ,
 `txt_liked_most` text ,
 `txt_improve` text ,
 `txt_recommendations` text ,
 `json_answers` text ,
 index `id` (`id`),
 index `date_filled` (`date_filled`),
 index `q_content_clear` (`q_content_clear`),
 index `q_met_expectations` (`q_met_expectations`),
 index `q_knowledge_useful` (`q_knowledge_useful`),
 index `q_lecturer_expert` (`q_lecturer_expert`),
 index `q_lecturer_communication` (`q_lecturer_communication`),
 index `q_lecturer_environment` (`q_lecturer_environment`),
 index `q_organization` (`q_organization`),
 index `q_tech_support` (`q_tech_support`),
 index `q_information` (`q_information`),
 index `q_overall_satisfaction` (`q_overall_satisfaction`),
 index `q_would_recommend` (`q_would_recommend`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `questionnaires`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    
  }

  public function downgradeForeignKeys(): void
  {
    
  }
}