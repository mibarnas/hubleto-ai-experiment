<?php

namespace Hubleto\App\Custom\Trainings\Controllers\Api;

/**
 * Generates an attendee's certificate. The attendee detail opens a form first,
 * so any field the user filled in there overrides the training's defaults.
 */
class GenerateCertificate extends \Hubleto\Erp\Controllers\ApiController
{
  private const OVERRIDABLE = [
    'internal_number',
    'date_internal_validity',
    'external_number',
    'date_external_validity',
    'name_validator',
    'date_expiration',
  ];

  public function response(): array
  {
    $idAttendee = $this->router()->urlParamAsInteger('idAttendee');
    if ($idAttendee <= 0) throw new \Exception('idAttendee is required.');

    $overrides = [];
    foreach (self::OVERRIDABLE as $field) {
      $value = $this->router()->urlParamAsString($field);
      if ($value !== '') $overrides[$field] = $value;
    }

    $result = $this->getService(\Hubleto\App\Custom\Trainings\CertificateGenerator::class)
      ->generate($idAttendee, true, $overrides);

    return [ 'status' => 'success' ] + $result;
  }
}
