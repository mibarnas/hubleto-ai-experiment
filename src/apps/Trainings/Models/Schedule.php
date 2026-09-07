<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Db\Column\Virtual;

class Schedule extends \Hubleto\Erp\Model
{
  public string $table = 'schedules';
  public string $recordManagerClass = RecordManagers\Schedule::class;
  public ?string $lookupSqlValue = 'concat({%TABLE%}.date_start)';
  public ?string $lookupUrlDetail = 'trainings/schedules/{%ID%}';
  public ?string $lookupUrlAdd = 'trainings/schedules/add';

  public array $relations = [
    'TRAINING' => [ self::BELONGS_TO, Training::class, 'id_training', 'id' ],
    'ATTENDEES' => [ self::HAS_MANY, Attendee::class, 'id_schedule', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_training' => (new Lookup($this, $this->translate('Training'), Training::class))->setRequired()->setDefaultVisible()
        ->setDefaultValue($this->router()->urlParamAsInteger('idTraining')),
      'date_start' => (new DateTime($this, $this->translate('Start')))->setRequired()->setDefaultVisible()
        ->setReactComponent('InputTimestamp')
        ->setDefaultValue(date('Y-m-d H:i:s')),
      'date_end' => (new DateTime($this, $this->translate('End')))->setReactComponent('InputTimestamp'),
      'meeting_link' => (new Varchar($this, $this->translate('Meeting link')))->setReactComponent('InputHyperlink')->setDefaultVisible(),
      'capacity' => (new Integer($this, $this->translate('Capacity'))),
      'note' => (new Text($this, $this->translate('Note'))),
      // Shown instead of a stored counter so the number can never drift from
      // the attendees actually registered for this date.
      'virt_attendees_count' => (new Virtual($this, $this->translate('Registered attendees')))->setDefaultVisible()
        ->setSearchAlgorithm('number')
        ->setProperty('sql', "
          select count(`a`.`id`) from `attendees` `a`
          where `a`.`id_schedule` = `schedules`.`id`
        "),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add Schedule');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

}
