<?php

namespace Hubleto\App\Custom\Trainings\Extendibles;

/**
 * The app menu shown next to the app name in the breadcrumbs.
 *
 * Desktop builds that menu from `collectExtendibles('AppMenu')` -- the
 * `AppMenuManager::addItem()` calls the app used to make are collected but
 * never rendered, which is why the schedules, attendees and certificates
 * tables had no link anywhere in the UI.
 */
class AppMenu extends \Hubleto\Framework\Extendible
{
  public function getItems(): array
  {
    return [
      [
        'app' => $this->app,
        'url' => 'trainings',
        'title' => $this->app->translate('Trainings'),
        'icon' => 'fas fa-chalkboard-user',
      ],
      [
        'app' => $this->app,
        'url' => 'trainings/schedules',
        'title' => $this->app->translate('Schedules'),
        'icon' => 'fas fa-calendar-days',
      ],
      [
        'app' => $this->app,
        'url' => 'trainings/attendees',
        'title' => $this->app->translate('Attendees'),
        'icon' => 'fas fa-user-graduate',
      ],
      [
        'app' => $this->app,
        'url' => 'trainings/certificates',
        'title' => $this->app->translate('Certificates'),
        'icon' => 'fas fa-certificate',
      ],
    ];
  }
}
