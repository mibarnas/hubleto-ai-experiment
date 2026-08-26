<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Pub;

use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Questionnaires\Models\Questionnaire;
use Hubleto\App\Custom\Questionnaires\Questions;

/**
 * Public, one-time satisfaction questionnaire.
 *
 * The spec requires the link to work exactly once: an unknown token, or a token
 * whose questionnaire has already been filled in, must return 404 rather than
 * reveal that the link ever existed.
 */
class FillQuestionnaire extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    parent::prepareView();

    $token = $this->router()->urlParamAsString('t');

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendee = strlen($token) > 0 ? $mAttendee->record->where('questionnaire_token', $token)->first() : null;

    if (!$attendee || !empty($attendee->date_questionnaire_filled)) {
      http_response_code(404);
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/NotFound.twig');
      return;
    }

    if ($this->router()->urlParamAsBool('submitted')) {
      $this->store((int) $attendee->id);
      $this->viewParams['submitted'] = true;
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/QuestionnaireDone.twig');
      return;
    }

    $this->viewParams['token'] = $token;
    $this->viewParams['ratings'] = Questions::RATINGS;
    $this->viewParams['freeTexts'] = Questions::FREE_TEXTS;
    $this->setView('@Hubleto:App:Custom:Trainings/Pub/Questionnaire.twig');
  }

  private function store(int $idAttendee): void
  {
    $answers = [];
    $record = [ 'date_filled' => date('Y-m-d H:i:s') ];

    foreach (array_keys(Questions::RATINGS) as $code) {
      $value = $this->router()->urlParamAsInteger($code) ?: null;
      $record[$code] = $value;
      $answers[$code] = $value;
    }

    foreach (array_keys(Questions::FREE_TEXTS) as $code) {
      $value = $this->router()->urlParamAsString($code);
      $record[$code] = $value;
      $answers[$code] = $value;
    }

    // Columns drive the indexed statistics; the JSON keeps the exact payload.
    $record['json_answers'] = json_encode($answers, JSON_UNESCAPED_UNICODE);

    $questionnaire = $this->getModel(Questionnaire::class)->record->recordCreate($record);

    $this->getModel(Attendee::class)->record->find($idAttendee)->update([
      'id_questionnaire' => $questionnaire['id'],
      'date_questionnaire_filled' => $record['date_filled'],
    ]);
  }
}
