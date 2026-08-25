<?php

namespace Hubleto\App\Custom\Trainings;

/**
 * Single source of truth for the satisfaction questionnaire's eleven rating
 * questions. Used by the QuestionnaireAnswer model's describeColumns(), the
 * public questionnaire form, the statistics chart endpoint and the CSV export
 * -- so wording changes happen in one place.
 */
class Questionnaire
{
  const QUESTIONS = [
    'q_content_clear' => 'The course content was understandable and well-structured',
    'q_met_expectations' => 'The course met my expectations',
    'q_knowledge_useful' => 'The acquired knowledge is useful to me',
    'q_lecturer_expert' => 'The lecturer was an expert on the topic',
    'q_lecturer_communication' => 'The lecturer communicated effectively and answered questions',
    'q_lecturer_environment' => 'The lecturer created a stimulating and supportive environment',
    'q_organization' => 'The organization of the course was effective',
    'q_tech_support' => 'Technical support (online platform) was satisfactory',
    'q_information' => 'Information about the course was available and understandable',
    'q_overall_satisfaction' => 'Overall satisfaction with the course',
    'q_would_recommend' => 'I would recommend this course to others',
  ];

  const FREE_TEXT_QUESTIONS = [
    'txt_liked_most' => 'What did you like most about the course?',
    'txt_improve' => 'What would you suggest improving?',
    'txt_recommendations' => 'Recommendations for future courses',
  ];
}
