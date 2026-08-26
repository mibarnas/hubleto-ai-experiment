<?php

namespace Hubleto\App\Custom\Trainings;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^trainings\/api\/get-statistics\/?$/' => Controllers\Api\GetStatistics::class,
      '/^trainings\/api\/send-meeting-link\/?$/' => Controllers\Api\SendMeetingLink::class,
      '/^trainings\/api\/send-questionnaire\/?$/' => Controllers\Api\SendQuestionnaire::class,
      '/^trainings\/api\/generate-certificate\/?$/' => Controllers\Api\GenerateCertificate::class,
      '/^trainings\/statistics\/export-csv\/?$/' => Controllers\ExportStatisticsCsv::class,
      '/^trainings\/certificates\/download\/?$/' => Controllers\DownloadCertificate::class,

      '/^training-questionnaire\/?$/' => Controllers\Pub\FillQuestionnaire::class,
      '/^training-catalog-sheet\/?$/' => Controllers\Pub\CatalogSheet::class,

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

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'trainings', $this->translate('Trainings'), 'fas fa-chalkboard-user');
    $appMenu->addItem($this, 'trainings/schedules', $this->translate('Schedules'), 'fas fa-calendar-days');
    $appMenu->addItem($this, 'trainings/attendees', $this->translate('Attendees'), 'fas fa-user-graduate');
    $appMenu->addItem($this, 'trainings/certificates', $this->translate('Certificates'), 'fas fa-certificate');
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
