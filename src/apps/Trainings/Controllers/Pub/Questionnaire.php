<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Pub;

use Hubleto\App\Custom\Trainings\Models\Applicant;
use Hubleto\App\Custom\Trainings\Models\QuestionnaireAnswer;
use Hubleto\App\Custom\Trainings\Questionnaire as QuestionnaireConstants;

class Questionnaire extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    parent::prepareView();

    $token = $this->router()->urlParamAsString('t');

    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    $applicant = $mApplicant->record->where('questionnaire_token', $token)->first();

    if (!$applicant) {
      $this->viewParams['error'] = $this->translate('This link is invalid.');
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/Questionnaire.twig');
      return;
    }

    $submitted = $this->router()->urlParamAsBool('submitted');

    if ($submitted && empty($applicant->questionnaire_filled_on)) {
      /** @var QuestionnaireAnswer */
      $mAnswer = $this->getModel(QuestionnaireAnswer::class);

      $data = [ 'id_applicant' => $applicant->id, 'filled_on' => date('Y-m-d H:i:s') ];
      foreach (QuestionnaireConstants::QUESTIONS as $code => $label) {
        $data[$code] = $this->router()->urlParamAsInteger($code) ?: null;
      }
      foreach (QuestionnaireConstants::FREE_TEXT_QUESTIONS as $code => $label) {
        $data[$code] = $this->router()->urlParamAsString($code);
      }
      $mAnswer->record->recordCreate($data);

      $mApplicant->record->find($applicant->id)->update(['questionnaire_filled_on' => date('Y-m-d H:i:s')]);
      $applicant = $mApplicant->record->find($applicant->id);
    }

    $this->viewParams['token'] = $token;
    $this->viewParams['alreadyFilled'] = !empty($applicant->questionnaire_filled_on);
    $this->viewParams['questions'] = array_map(fn($label) => $this->translate($label), QuestionnaireConstants::QUESTIONS);
    $this->viewParams['freeTextQuestions'] = array_map(fn($label) => $this->translate($label), QuestionnaireConstants::FREE_TEXT_QUESTIONS);

    $this->setView('@Hubleto:App:Custom:Trainings/Pub/Questionnaire.twig');
  }

}
