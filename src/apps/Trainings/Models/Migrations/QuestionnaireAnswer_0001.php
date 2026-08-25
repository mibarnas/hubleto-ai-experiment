<?php

namespace Hubleto\App\Custom\Trainings\Models\Migrations;

use Hubleto\Framework\Migration;

class QuestionnaireAnswer_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_questionnaire_answers`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `training_questionnaire_answers` (
 `id` int(8) primary key auto_increment,
 `id_applicant` int(8) NULL default NULL,
 `filled_on` datetime ,
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
 index `id` (`id`),
 index `id_applicant` (`id_applicant`),
 index `filled_on` (`filled_on`),
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
SET foreign_key_checks = 1;


              alter table `training_questionnaire_answers`
              add constraint `id_applicant` unique (`id_applicant` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `training_questionnaire_answers`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_questionnaire_answers`
          ADD CONSTRAINT `fk_43308ab8eae079717d33880c5042bc73`
          FOREIGN KEY (`id_applicant`)
          REFERENCES `training_applicants` (`id`)
          ON DELETE RESTRICT
          ON UPDATE RESTRICT;");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `training_questionnaire_answers`
          DROP FOREIGN KEY `fk_43308ab8eae079717d33880c5042bc73`;");
  }
}