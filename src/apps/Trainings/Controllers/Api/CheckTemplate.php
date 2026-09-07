<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

use Hubleto\App\Custom\Trainings\Models\Training;
use Hubleto\App\Custom\Trainings\TemplateVariables;

/**
 * Re-scans a training's certificate template and reports what it asks for.
 *
 * The training form calls this after an upload so the user is told immediately
 * which values the template uses, which of them are not supported (a typo, or
 * something nobody has implemented yet), and which required values it forgot.
 */
class CheckTemplate extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idTraining = $this->router()->urlParamAsInteger('idTraining');
    if ($idTraining <= 0) throw new \Exception($this->translate('idTraining is required.'));

    /** @var Training */
    $mTraining = $this->getModel(Training::class);
    $mTraining->refreshTemplateParams($idTraining);

    $training = $mTraining->record->find($idTraining);
    $analysis = (array) json_decode((string) ($training->template_params ?? ''), true);

    return [
      'status' => 'success',
      'analysis' => $analysis,
      // The full catalogue, so the form can show the author every value that is
      // available to put on a template.
      'available' => array_map(fn($key) => [
        'key' => $key,
        'label' => $this->translate(TemplateVariables::label($key)),
        'group' => TemplateVariables::CATALOG[$key]['group'],
        'required' => TemplateVariables::CATALOG[$key]['required'],
      ], TemplateVariables::keys()),
    ];
  }
}
