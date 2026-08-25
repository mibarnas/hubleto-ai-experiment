import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';

/** Mirrors Hubleto\App\Custom\Trainings\Questionnaire::QUESTIONS. */
export const RATING_QUESTIONS = [
  'q_content_clear',
  'q_met_expectations',
  'q_knowledge_useful',
  'q_lecturer_expert',
  'q_lecturer_communication',
  'q_lecturer_environment',
  'q_organization',
  'q_tech_support',
  'q_information',
  'q_overall_satisfaction',
  'q_would_recommend',
];

export const FREE_TEXT_QUESTIONS = [
  'txt_liked_most',
  'txt_improve',
  'txt_recommendations',
];

export interface FormQuestionnaireAnswerProps extends FormExtendedProps { }
export interface FormQuestionnaireAnswerState extends FormExtendedState { }

export default class FormQuestionnaireAnswer<P, S> extends FormExtended<FormQuestionnaireAnswerProps, FormQuestionnaireAnswerState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-clipboard-question',
    model: 'Hubleto/App/Custom/Trainings/Models/QuestionnaireAnswer',
  }

  props: FormQuestionnaireAnswerProps;
  state: FormQuestionnaireAnswerState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormQuestionnaireAnswer';

  constructor(props: FormQuestionnaireAnswerProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Questionnaire')}</b> },
    ];
  }

  renderTitle(): JSX.Element {
    return <>
      <small>{this.translate('Satisfaction questionnaire')}</small>
      <h2>{this.state.record.filled_on ?? this.translate('Not filled in yet')}</h2>
    </>;
  }

  renderTab(tabUid: string) {
    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Ratings (1 = worst, 5 = best)')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_applicant')}
              {this.inputWrapper('filled_on')}
              {this.divider(this.translate('Questions'))}
              {RATING_QUESTIONS.map((q) => <div key={q}>{this.inputWrapper(q)}</div>)}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Comments')}</div>
            <div className='card-body'>
              {FREE_TEXT_QUESTIONS.map((q) => <div key={q}>{this.inputWrapper(q)}</div>)}
            </div>
          </div>
        </div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
