<?php

namespace Hubleto\App\Custom\Certificates\Controllers\Api;

class Generate extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $idApplicant = $this->router()->urlParamAsInteger('idApplicant');
    if ($idApplicant <= 0) throw new \Exception('idApplicant is required.');

    $result = $this->getService(\Hubleto\App\Custom\Certificates\CertificateGenerator::class)->generate($idApplicant);

    return [ 'status' => 'success' ] + $result;
  }
}
