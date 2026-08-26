import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormAttendee, { FormAttendeeProps } from './FormAttendee';

export interface TableAttendeesProps extends TableExtendedProps {
  idSchedule?: number,
  idOrder?: number,
  idWorker?: number,
}

export interface TableAttendeesState extends TableExtendedState { }

export default class TableAttendees extends TableExtended<TableAttendeesProps, TableAttendeesState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/Attendee',
  }

  props: TableAttendeesProps;
  state: TableAttendeesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableAttendees';

  constructor(props: TableAttendeesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by Attendee's RecordManager prepareReadQuery(). */
  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idSchedule: this.props.idSchedule,
      idOrder: this.props.idOrder,
      idWorker: this.props.idWorker,
    };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/attendees' + (id > 0 ? '/' + id : ''));
  }

  renderForm(): JSX.Element {
    let formProps: FormAttendeeProps = this.getFormProps() as FormAttendeeProps;
    formProps.uid = this.props.uid + '_form_attendee';

    let defaults: any = {};
    if (this.props.idSchedule) defaults.id_schedule = this.props.idSchedule;
    if (this.props.idOrder) defaults.id_order = this.props.idOrder;
    if (this.props.idWorker) defaults.id_worker = this.props.idWorker;

    formProps.description = formProps.description ?? {};
    formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, ...defaults };
    formProps.customEndpointParams = {
      ...formProps.customEndpointParams ?? {},
      idSchedule: this.props.idSchedule,
      idOrder: this.props.idOrder,
      idWorker: this.props.idWorker,
    };

    return <FormAttendee {...formProps}/>;
  }
}
