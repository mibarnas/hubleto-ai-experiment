<?php

namespace Hubleto\App\Custom\Trainings;

class Loader extends \Hubleto\Erp\App
{

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^trainings\/api\/statistics\/?$/' => Controllers\Api\Statistics::class,
      '/^trainings\/statistics\/export-csv\/?$/' => Controllers\StatisticsExportCsv::class,
      '/^trainings\/api\/send-meeting-link\/?$/' => Controllers\Api\SendMeetingLink::class,
      '/^trainings\/api\/send-questionnaire\/?$/' => Controllers\Api\SendQuestionnaire::class,
      '/^trainings\/api\/import-applicants\/?$/' => Controllers\Api\ImportApplicants::class,
      '/^trainings\/api\/recalculate-order\/?$/' => Controllers\Api\RecalculateOrder::class,

      '/^training-questionnaire\/?$/' => Controllers\Pub\Questionnaire::class,
      '/^training-catalog-sheet\/?$/' => Controllers\Pub\CatalogSheet::class,

      '/^trainings\/dates\/add\/?$/' => ['controller' => Controllers\Dates::class, 'vars' => ['recordId' => -1]],
      '/^trainings\/dates(\/(?<recordId>\d+))?\/?$/' => Controllers\Dates::class,
      '/^trainings\/applicants(\/(?<recordId>\d+))?\/?$/' => Controllers\Applicants::class,
      '/^trainings\/orders\/add\/?$/' => ['controller' => Controllers\Orders::class, 'vars' => ['recordId' => -1]],
      '/^trainings\/orders(\/(?<recordId>\d+))?\/?$/' => Controllers\Orders::class,

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
    $appMenu->addItem($this, 'trainings/dates', $this->translate('Dates'), 'fas fa-calendar-days');
    $appMenu->addItem($this, 'trainings/applicants', $this->translate('Applicants'), 'fas fa-user-graduate');
    $appMenu->addItem($this, 'trainings/orders', $this->translate('Orders'), 'fas fa-file-invoice');
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Training::class)->upgradeSchema();
      $this->getModel(Models\TrainingDate::class)->upgradeSchema();
      $this->getModel(Models\TrainingOrder::class)->upgradeSchema();
      $this->getModel(Models\Applicant::class)->upgradeSchema();
      $this->getModel(Models\QuestionnaireAnswer::class)->upgradeSchema();
    }
    if ($round == 2) {
      // do something in the 2nd round, if required
    }
    if ($round == 3) {
      // do something in the 3rd round, if required
    }
  }

  public function generateDemoData(): void
  {
    // Create any demo data to promote your app.
  }

}
