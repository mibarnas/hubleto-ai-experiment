import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormTraining, { FormTrainingProps } from './FormTraining';

export interface TableTrainingsProps extends TableExtendedProps { }
export interface TableTrainingsState extends TableExtendedState { }

export default class TableTrainings extends TableExtended<TableTrainingsProps, TableTrainingsState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/Training',
  }

  props: TableTrainingsProps;
  state: TableTrainingsState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableTrainings';

  constructor(props: TableTrainingsProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  getCsvImportEndpointParams(): any {
    return { model: this.props.model };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormTrainingProps = this.getFormProps() as FormTrainingProps;
    formProps.uid = this.props.uid + '_form_training';
    return <FormTraining {...formProps}/>;
  }
}
