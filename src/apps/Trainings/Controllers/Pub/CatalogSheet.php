<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Pub;

use Hubleto\App\Custom\Trainings\Models\Applicant;

class CatalogSheet extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    parent::prepareView();

    $token = $this->router()->urlParamAsString('t');

    /** @var Applicant */
    $mApplicant = $this->getModel(Applicant::class);
    $applicant = $mApplicant->record->where('catalog_token', $token)->with('WORKER')->first();

    if (!$applicant) {
      $this->viewParams['error'] = $this->translate('This link is invalid.');
      $this->setView('@Hubleto:App:Custom:Trainings/Pub/CatalogSheet.twig');
      return;
    }

    $submitted = $this->router()->urlParamAsBool('submitted');

    if ($submitted && empty($applicant->catalog_filled_on)) {
      $mApplicant->record->find($applicant->id)->update([
        'education_level' => $this->router()->urlParamAsInteger('education_level') ?: null,
        'financing_type' => $this->router()->urlParamAsInteger('financing_type') ?: null,
        'catalog_filled_on' => date('Y-m-d H:i:s'),
      ]);
      $applicant = $mApplicant->record->find($applicant->id)->load('WORKER');
    }

    $this->viewParams['token'] = $token;
    $this->viewParams['worker'] = $applicant->WORKER;
    $this->viewParams['alreadyFilled'] = !empty($applicant->catalog_filled_on);
    $this->viewParams['educationLevels'] = array_map(fn($v) => $this->translate($v), Applicant::EDUCATION_VALUES);
    $this->viewParams['financingTypes'] = array_map(fn($v) => $this->translate($v), Applicant::FINANCING_VALUES);

    $this->setView('@Hubleto:App:Custom:Trainings/Pub/CatalogSheet.twig');
  }

}
