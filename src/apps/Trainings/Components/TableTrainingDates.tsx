import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormTrainingDate, { FormTrainingDateProps } from './FormTrainingDate';

export interface TableTrainingDatesProps extends TableExtendedProps {
  idTraining?: number,
}

export interface TableTrainingDatesState extends TableExtendedState { }

export default class TableTrainingDates extends TableExtended<TableTrainingDatesProps, TableTrainingDatesState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/TrainingDate',
  }

  props: TableTrainingDatesProps;
  state: TableTrainingDatesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableTrainingDates';

  constructor(props: TableTrainingDatesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by TrainingDate's RecordManager `prepareReadQuery()`. */
  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idTraining: this.props.idTraining,
    };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/dates/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormTrainingDateProps = this.getFormProps() as FormTrainingDateProps;
    formProps.uid = this.props.uid + '_form_training_date';

    if (this.props.idTraining) {
      formProps.description = formProps.description ?? {};
      formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, id_training: this.props.idTraining };
      formProps.customEndpointParams = { ...formProps.customEndpointParams ?? {}, idTraining: this.props.idTraining };
    }

    return <FormTrainingDate {...formProps}/>;
  }
}
