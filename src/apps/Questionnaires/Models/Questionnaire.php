<?php

namespace Hubleto\App\Custom\Questionnaires\Models;

use Hubleto\App\Custom\Questionnaires\Questions;

use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;

class Questionnaire extends \Hubleto\Erp\Model
{
  public string $table = 'questionnaires';
  public string $recordManagerClass = RecordManagers\Questionnaire::class;
  public ?string $lookupSqlValue = 'concat("Questionnaire #", {%TABLE%}.id)';
  public ?string $lookupUrlDetail = 'questionnaires/{%ID%}';

  public array $relations = [
    'ATTENDEE' => [ self::BELONGS_TO, \Hubleto\App\Custom\Trainings\Models\Attendee::class, 'id_attendee', 'id' ],
  ];

  public function describeColumns(): array
  {
    $ratings = [];
    foreach (Questions::RATINGS as $code => $label) {
      $ratings[$code] = (new Integer($this, $this->translate($label)))
        ->setEnumValues([ 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5' ]);
    }

    $freeTexts = [];
    foreach (Questions::FREE_TEXTS as $code => $label) {
      $freeTexts[$code] = (new Text($this, $this->translate($label)));
    }

    return array_merge(parent::describeColumns(), [
      // Who filled the questionnaire in. Questionnaires installs before
      // Trainings, so the constraint is skipped -- the attendee also points
      // back here through `id_questionnaire`.
      'id_attendee' => (new Lookup($this, $this->translate('Attendee'), \Hubleto\App\Custom\Trainings\Models\Attendee::class))
        ->setReadonly()
        ->setDefaultVisible()
        ->setProperty('disableForeignKey', true)
      ,
      'date_filled' => (new DateTime($this, $this->translate('Filled on')))->setReadonly()->setDefaultVisible()->setReactComponent('InputTimestamp'),
    ], $ratings, $freeTexts, [
      // The spec stores answers as JSON. The rating/free-text columns above exist
      // so per-question averages and the date-interval CSV export stay indexed
      // queries; this keeps the exact submitted payload for audit.
      'json_answers' => (new Json($this, $this->translate('Raw answers (JSON)')))->setReadonly()->setDefaultHidden(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['title'] = $this->translate('Questionnaires');
    $description->show(['header', 'fulltextSearch', 'columnSearch']);
    $description->hide(['footer']);
    // A questionnaire only ever comes from an attendee submitting the public
    // form, so it must not be creatable by hand.
    $description->permissions['canCreate'] = false;
    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->permissions['canCreate'] = false;
    return $description;
  }
}
