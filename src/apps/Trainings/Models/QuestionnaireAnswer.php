<?php

namespace Hubleto\App\Custom\Trainings\Models;

use Hubleto\App\Custom\Trainings\Questionnaire;

use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;

class QuestionnaireAnswer extends \Hubleto\Erp\Model
{
  public string $table = 'training_questionnaire_answers';
  public string $recordManagerClass = RecordManagers\QuestionnaireAnswer::class;
  public ?string $lookupSqlValue = 'concat("Questionnaire #", {%TABLE%}.id)';

  public array $relations = [
    'APPLICANT' => [ self::BELONGS_TO, Applicant::class, 'id_applicant', 'id' ],
  ];

  public function describeColumns(): array
  {
    $ratingColumns = [];
    foreach (Questionnaire::QUESTIONS as $code => $label) {
      $ratingColumns[$code] = (new Integer($this, $this->translate($label)))
        ->setEnumValues([ 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5' ]);
    }

    return array_merge(parent::describeColumns(), [
      'id_applicant' => (new Lookup($this, $this->translate('Applicant'), Applicant::class))->setRequired()->setDefaultVisible(),
      'filled_on' => (new DateTime($this, $this->translate('Filled on')))->setReadonly()->setDefaultVisible(),
    ], $ratingColumns, [
      'txt_liked_most' => (new Text($this, $this->translate('What did you like most about the course?'))),
      'txt_improve' => (new Text($this, $this->translate('What would you suggest improving?'))),
      'txt_recommendations' => (new Text($this, $this->translate('Recommendations for future courses'))),
    ]);
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'id_applicant' => [
        'type' => 'unique',
        'columns' => [ 'id_applicant' => [ 'order' => 'asc' ] ],
      ],
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->show(['header', 'fulltextSearch']);
    $description->hide(['footer']);
    return $description;
  }

}
