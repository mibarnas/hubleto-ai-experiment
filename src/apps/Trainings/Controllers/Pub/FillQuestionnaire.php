<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Pub;

use Hubleto\App\Custom\Trainings\Models\Attendee;
use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Questionnaires\Models\Questionnaire;
use Hubleto\App\Custom\Questionnaires\Questions;

/**
 * Public, one-time form combining the catalog sheet and the satisfaction
 * questionnaire.
 *
 * The two used to be separate pages, which meant two links per attendee and no
 * way to correct the personal data the certificate is printed from. They are
 * now one form: the attendee confirms (and completes) their own details, fills
 * in the catalog sheet, and answers the questionnaire in a single pass.
 *
 * The link works exactly once: an unknown token, or a token whose questionnaire
 * has already been filled in, returns 404 rather than revealing that the link
 * ever existed.
 */
class FillQuestionnaire extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;

  /**
   * Personal details the attendee may complete or correct. Everything else on
   * the worker record is administrative and not editable from a public page.
   */
  private const WORKER_FIELDS = [
    'title_before', 'first_name', 'last_name', 'title_after',
    'birth_number', 'phone',
    'address', 'city', 'zip',
    'workplace_name', 'workplace_address', 'workplace_city', 'workplace_zip',
  ];

  public function prepareView(): void
  {
    parent::prepareView();

    $token = $this->router()->urlParamAsString('t');
    $attendee = $this->findByToken($token);

    if (!$attendee || !empty($attendee->date_questionnaire_filled)) {
      http_response_code(404);
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/NotFound.twig');
      return;
    }

    if ($this->router()->urlParamAsBool('submitted')) {
      $this->store($attendee);
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/QuestionnaireDone.twig');
      return;
    }

    $worker = $this->getModel(Worker::class)->record->find($attendee->id_worker);

    $this->viewParams['token'] = $token;
    $this->viewParams['worker'] = $worker;
    $this->viewParams['workerFields'] = self::WORKER_FIELDS;
    $this->viewParams['workerLabels'] = $this->getWorkerLabels();
    $this->viewParams['educationLevels'] = array_map(fn($v) => $this->translate($v), Attendee::EDUCATION_VALUES);
    $this->viewParams['financingTypes'] = array_map(fn($v) => $this->translate($v), Attendee::FINANCING_VALUES);
    $this->viewParams['educationSelected'] = (int) $attendee->education_level;
    $this->viewParams['financingSelected'] = (int) $attendee->financing_type;
    $this->viewParams['ratings'] = Questions::RATINGS;
    $this->viewParams['freeTexts'] = Questions::FREE_TEXTS;

    $this->setView('@Hubleto:App:Custom:Trainings/Pub/Questionnaire.twig');
  }

  /**
   * Links mailed out before the two forms were merged carried the catalog
   * token, so both tokens still resolve to this page.
   */
  private function findByToken(string $token): mixed
  {
    if (strlen($token) === 0) return null;

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);

    return $mAttendee->record
      ->where(fn($q) => $q->where('questionnaire_token', $token)->orWhere('catalog_token', $token))
      ->first()
    ;
  }

  private function store(mixed $attendee): void
  {
    $now = date('Y-m-d H:i:s');

    $this->updateWorker((int) $attendee->id_worker);

    $answers = [];
    $record = [ 'date_filled' => $now, 'id_attendee' => (int) $attendee->id ];

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

    $this->getModel(Attendee::class)->record->find($attendee->id)->update([
      'id_questionnaire' => $questionnaire['id'],
      'date_questionnaire_filled' => $now,
      'education_level' => $this->router()->urlParamAsInteger('education_level') ?: null,
      'financing_type' => $this->router()->urlParamAsInteger('financing_type') ?: null,
      'date_catalog_filled' => $now,
    ]);
  }

  /**
   * Writes back what the attendee filled in. Only non-empty values are applied,
   * so leaving a field blank never wipes data the office already has.
   */
  private function updateWorker(int $idWorker): void
  {
    if ($idWorker <= 0) return;

    $update = [];
    foreach (self::WORKER_FIELDS as $field) {
      $value = trim($this->router()->urlParamAsString($field));
      if ($value !== '') $update[$field] = $value;
    }

    if (empty($update)) return;

    $this->getModel(Worker::class)->record->find($idWorker)?->update($update);
  }

  /** @return array<string, string> */
  private function getWorkerLabels(): array
  {
    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    $columns = $mWorker->getColumns();

    $labels = [];
    foreach (self::WORKER_FIELDS as $field) {
      $labels[$field] = isset($columns[$field]) ? $columns[$field]->getTitle() : $field;
    }

    return $labels;
  }
}
