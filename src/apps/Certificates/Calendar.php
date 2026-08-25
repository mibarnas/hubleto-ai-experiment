<?php

namespace Hubleto\App\Custom\Certificates;

class Calendar extends \Hubleto\App\Community\Calendar\Calendar {

  public array $calendarConfig = [
    "title" => "Certificates",
    "addNewActivityButtonText" => "Add new activity linked to Certificates",
    "icon" => "fas fa-calendar",
    "formComponent" => "CertificatesFormActivity"
  ];

  public function loadEvent(int $id): array
  {
    // Implement your functionality for loading single calendar event.
    return [];
  }

  public function loadEvents(string $dateStart, string $dateEnd, array $filter = [], $idUser = 0): array
  {
    // Implement your functionality for loading multiple calendar events.
    return [];
  }

}