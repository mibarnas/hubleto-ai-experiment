<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

use Hubleto\App\Community\Auth\Models\User;

class Schedule extends \Hubleto\Erp\Model
{
  public string $table = 'schedules';
  public string $recordManagerClass = RecordManagers\Schedule::class;
  public ?string $lookupSqlValue = 'concat({%TABLE%}.date_start)';
  public ?string $lookupUrlDetail = 'trainings/schedules/{%ID%}';
  public ?string $lookupUrlAdd = 'trainings/schedules/add';

  public array $relations = [
    'TRAINING' => [ self::BELONGS_TO, Training::class, 'id_training', 'id' ],
    'LECTURER' => [ self::BELONGS_TO, User::class, 'id_lecturer', 'id' ],
    'ATTENDEES' => [ self::HAS_MANY, Attendee::class, 'id_schedule', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_training' => (new Lookup($this, $this->translate('Training'), Training::class))->setRequired()->setDefaultVisible()
        ->setDefaultValue($this->router()->urlParamAsInteger('idTraining')),
      'date_start' => (new DateTime($this, $this->translate('Start')))->setRequired()->setDefaultVisible()->setDefaultValue(date('Y-m-d H:i:s')),
      'date_end' => (new DateTime($this, $this->translate('End'))),
      'meeting_link' => (new Varchar($this, $this->translate('Meeting link')))->setReactComponent('InputHyperlink')->setDefaultVisible(),
      'id_lecturer' => (new Lookup($this, $this->translate('Lecturer'), User::class))->setReactComponent('InputUserSelect')->setDefaultVisible(),
      'capacity' => (new Integer($this, $this->translate('Capacity'))),
      'note' => (new Text($this, $this->translate('Note'))),
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
