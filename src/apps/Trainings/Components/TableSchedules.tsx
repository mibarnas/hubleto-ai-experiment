import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormSchedule, { FormScheduleProps } from './FormSchedule';

export interface TableSchedulesProps extends TableExtendedProps {
  idTraining?: number,
  idWorker?: number,
}

export interface TableSchedulesState extends TableExtendedState { }

export default class TableSchedules extends TableExtended<TableSchedulesProps, TableSchedulesState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/Schedule',
  }

  props: TableSchedulesProps;
  state: TableSchedulesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableSchedules';

  constructor(props: TableSchedulesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by Schedule's RecordManager prepareReadQuery(). */
  getEndpointParams(): any {
    return { ...super.getEndpointParams(), idTraining: this.props.idTraining, idWorker: this.props.idWorker };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/schedules/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormScheduleProps = this.getFormProps() as FormScheduleProps;
    formProps.uid = this.props.uid + '_form_schedule';

    if (this.props.idTraining) {
      formProps.description = formProps.description ?? {};
      formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, id_training: this.props.idTraining };
      formProps.customEndpointParams = { ...formProps.customEndpointParams ?? {}, idTraining: this.props.idTraining };
    }

    return <FormSchedule {...formProps}/>;
  }
}
