import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormApplicant, { FormApplicantProps } from './FormApplicant';

export interface TableApplicantsProps extends TableExtendedProps {
  idTrainingDate?: number,
  idOrder?: number,
  idWorker?: number,
}

export interface TableApplicantsState extends TableExtendedState { }

export default class TableApplicants extends TableExtended<TableApplicantsProps, TableApplicantsState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/Applicant',
  }

  props: TableApplicantsProps;
  state: TableApplicantsState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableApplicants';

  constructor(props: TableApplicantsProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by Applicant's RecordManager `prepareReadQuery()`. */
  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idTrainingDate: this.props.idTrainingDate,
      idOrder: this.props.idOrder,
      idWorker: this.props.idWorker,
    };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/applicants' + (id > 0 ? '/' + id : ''));
  }

  renderForm(): JSX.Element {
    let formProps: FormApplicantProps = this.getFormProps() as FormApplicantProps;
    formProps.uid = this.props.uid + '_form_applicant';

    let defaultValues: any = {};
    if (this.props.idTrainingDate) defaultValues.id_training_date = this.props.idTrainingDate;
    if (this.props.idOrder) defaultValues.id_order = this.props.idOrder;
    if (this.props.idWorker) defaultValues.id_worker = this.props.idWorker;

    formProps.description = formProps.description ?? {};
    formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, ...defaultValues };
    formProps.customEndpointParams = {
      ...formProps.customEndpointParams ?? {},
      idTrainingDate: this.props.idTrainingDate,
      idOrder: this.props.idOrder,
      idWorker: this.props.idWorker,
    };

    return <FormApplicant {...formProps}/>;
  }
}
