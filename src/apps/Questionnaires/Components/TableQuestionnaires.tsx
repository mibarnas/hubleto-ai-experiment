import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormQuestionnaire, { FormQuestionnaireProps } from './FormQuestionnaire';

export interface TableQuestionnairesProps extends TableExtendedProps { }
export interface TableQuestionnairesState extends TableExtendedState { }

export default class TableQuestionnaires extends TableExtended<TableQuestionnairesProps, TableQuestionnairesState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Questionnaires/Models/Questionnaire',
  }

  props: TableQuestionnairesProps;
  state: TableQuestionnairesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Questionnaires\\Loader';
  translationContextInner: string = 'Components\\TableQuestionnaires';

  constructor(props: TableQuestionnairesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/questionnaires' + (id > 0 ? '/' + id : ''));
  }

  renderForm(): JSX.Element {
    let formProps: FormQuestionnaireProps = this.getFormProps() as FormQuestionnaireProps;
    formProps.uid = this.props.uid + '_form_questionnaire';
    return <FormQuestionnaire {...formProps}/>;
  }
}
