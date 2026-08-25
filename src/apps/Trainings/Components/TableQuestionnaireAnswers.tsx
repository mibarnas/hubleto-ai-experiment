import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormQuestionnaireAnswer, { FormQuestionnaireAnswerProps } from './FormQuestionnaireAnswer';

export interface TableQuestionnaireAnswersProps extends TableExtendedProps {
  idApplicant?: number,
}

export interface TableQuestionnaireAnswersState extends TableExtendedState { }

export default class TableQuestionnaireAnswers extends TableExtended<TableQuestionnaireAnswersProps, TableQuestionnaireAnswersState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/QuestionnaireAnswer',
  }

  props: TableQuestionnaireAnswersProps;
  state: TableQuestionnaireAnswersState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableQuestionnaireAnswers';

  constructor(props: TableQuestionnaireAnswersProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idApplicant: this.props.idApplicant,
    };
  }

  renderForm(): JSX.Element {
    let formProps: FormQuestionnaireAnswerProps = this.getFormProps() as FormQuestionnaireAnswerProps;
    formProps.uid = this.props.uid + '_form_questionnaire_answer';
    if (this.props.idApplicant) {
      formProps.description = formProps.description ?? {};
      formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, id_applicant: this.props.idApplicant };
    }
    return <FormQuestionnaireAnswer {...formProps}/>;
  }
}
