import React from 'react'
import { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import FormAlgo from '../../Trainings/Components/FormAlgo';

/** Mirrors Hubleto\App\Custom\Questionnaires\Questions. */
export const RATING_QUESTIONS = [
  'q_content_clear', 'q_met_expectations', 'q_knowledge_useful',
  'q_lecturer_expert', 'q_lecturer_communication', 'q_lecturer_environment',
  'q_organization', 'q_tech_support', 'q_information',
  'q_overall_satisfaction', 'q_would_recommend',
];

export const FREE_TEXT_QUESTIONS = ['txt_liked_most', 'txt_improve', 'txt_recommendations'];

export interface FormQuestionnaireProps extends FormExtendedProps { }
export interface FormQuestionnaireState extends FormExtendedState { }

export default class FormQuestionnaire<P, S> extends FormAlgo<FormQuestionnaireProps, FormQuestionnaireState> {
  static defaultProps: any = {
    ...FormAlgo.defaultProps,
    icon: 'fas fa-clipboard-question',
    model: 'Hubleto/App/Custom/Questionnaires/Models/Questionnaire',
  }

  props: FormQuestionnaireProps;
  state: FormQuestionnaireState;

  parentApp: string = 'Hubleto/App/Custom/Questionnaires';

  translationContext: string = 'Hubleto\\App\\Custom\\Questionnaires\\Loader';
  translationContextInner: string = 'Components\\FormQuestionnaire';

  constructor(props: FormQuestionnaireProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getMainTab() {
    return { uid: 'default', title: <b>{this.translate('Questionnaire')}</b> };
  }

  getRecordFormUrl(): string {
    return this.state.record.id > 0 ? 'questionnaires/' + this.state.record.id : '';
  }

  renderTitle(): JSX.Element {
    return <>
      <small>{this.translate('Satisfaction questionnaire')}</small>
      <h2>{this.state.record.date_filled ?? this.translate('Not filled in yet')}</h2>
    </>;
  }

  renderTab(tabUid: string) {
    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Ratings (1 = worst, 5 = best)')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_attendee')}
              {this.inputWrapper('date_filled')}
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
