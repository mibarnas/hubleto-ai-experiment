<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Pub;

use Hubleto\App\Custom\Trainings\Models\Attendee;

class CatalogSheet extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    parent::prepareView();

    $token = $this->router()->urlParamAsString('t');

    /** @var Attendee */
    $mAttendee = $this->getModel(Attendee::class);
    $attendee = $mAttendee->record->where('catalog_token', $token)->with('WORKER')->first();

    if (!$attendee) {
      $this->viewParams['error'] = $this->translate('This link is invalid.');
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/CatalogSheet.twig');
      return;
    }

    $submitted = $this->router()->urlParamAsBool('submitted');

    if ($submitted && empty($attendee->date_catalog_filled)) {
      $mAttendee->record->find($attendee->id)->update([
        'education_level' => $this->router()->urlParamAsInteger('education_level') ?: null,
        'financing_type' => $this->router()->urlParamAsInteger('financing_type') ?: null,
        'date_catalog_filled' => date('Y-m-d H:i:s'),
      ]);
      $attendee = $mAttendee->record->find($attendee->id)->load('WORKER');
    }

    $this->viewParams['token'] = $token;
    $this->viewParams['worker'] = $attendee->WORKER;
    $this->viewParams['alreadyFilled'] = !empty($attendee->date_catalog_filled);
    $this->viewParams['educationLevels'] = array_map(fn($v) => $this->translate($v), Attendee::EDUCATION_VALUES);
    $this->viewParams['financingTypes'] = array_map(fn($v) => $this->translate($v), Attendee::FINANCING_VALUES);

    $this->setView('@Hubleto:App:Custom:Trainings/Pub/CatalogSheet.twig');
  }

}
