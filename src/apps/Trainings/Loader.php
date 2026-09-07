<?php

namespace Hubleto\App\Custom\Trainings;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^trainings\/api\/send-meeting-link\/?$/' => Controllers\Api\SendMeetingLink::class,
      '/^trainings\/api\/send-questionnaire\/?$/' => Controllers\Api\SendQuestionnaire::class,
      '/^trainings\/api\/generate-certificate\/?$/' => Controllers\Api\GenerateCertificate::class,
      '/^trainings\/api\/check-template\/?$/' => Controllers\Api\CheckTemplate::class,
      '/^trainings\/certificates\/download\/?$/' => Controllers\DownloadCertificate::class,

      // One public form covering the catalog sheet and the questionnaire. The
      // old catalog-sheet URL is kept so links already emailed out still work.
      '/^training-questionnaire\/?$/' => Controllers\Pub\FillQuestionnaire::class,
      '/^training-catalog-sheet\/?$/' => Controllers\Pub\FillQuestionnaire::class,

      '/^trainings\/schedules\/add\/?$/' => ['controller' => Controllers\Schedules::class, 'vars' => ['recordId' => -1]],
      '/^trainings\/schedules(\/(?<recordId>\d+))?\/?$/' => Controllers\Schedules::class,
      '/^trainings\/attendees(\/(?<recordId>\d+))?\/?$/' => Controllers\Attendees::class,
      '/^trainings\/certificates(\/(?<recordId>\d+))?\/?$/' => Controllers\Certificates::class,

      '/^trainings\/add\/?$/' => ['controller' => Controllers\Trainings::class, 'vars' => ['recordId' => -1]],
      '/^trainings(\/(?<recordId>\d+))?\/?$/' => Controllers\Trainings::class,
      '/^settings\/trainings\/?$/' => Controllers\Settings::class,
    ]);

    $settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Trainings'),
      'icon' => 'fas fa-chalkboard-user',
      'url' => 'settings/trainings',
    ]);
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      // Certificates first: attendees carry the id_certificate foreign key.
      $this->getModel(Models\Training::class)->upgradeSchema();
      $this->getModel(Models\Schedule::class)->upgradeSchema();
      $this->getModel(Models\Certificate::class)->upgradeSchema();
      $this->getModel(Models\Attendee::class)->upgradeSchema();
    }
  }

  public function generateDemoData(): void
  {
  }

}
