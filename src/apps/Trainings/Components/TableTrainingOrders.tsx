import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormTrainingOrder, { FormTrainingOrderProps } from './FormTrainingOrder';

export interface TableTrainingOrdersProps extends TableExtendedProps {
  idWorker?: number,
  idCustomer?: number,
  idTrainingDate?: number,
}

export interface TableTrainingOrdersState extends TableExtendedState { }

export default class TableTrainingOrders extends TableExtended<TableTrainingOrdersProps, TableTrainingOrdersState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/TrainingOrder',
  }

  props: TableTrainingOrdersProps;
  state: TableTrainingOrdersState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableTrainingOrders';

  constructor(props: TableTrainingOrdersProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by TrainingOrder's RecordManager `prepareReadQuery()`. */
  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idWorker: this.props.idWorker,
      idCustomer: this.props.idCustomer,
      idTrainingDate: this.props.idTrainingDate,
    };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/orders/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormTrainingOrderProps = this.getFormProps() as FormTrainingOrderProps;
    formProps.uid = this.props.uid + '_form_training_order';

    let defaultValues: any = {};
    if (this.props.idWorker) defaultValues.id_worker = this.props.idWorker;
    if (this.props.idCustomer) defaultValues.id_customer = this.props.idCustomer;
    if (this.props.idTrainingDate) defaultValues.id_training_date = this.props.idTrainingDate;

    formProps.description = formProps.description ?? {};
    formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, ...defaultValues };

    return <FormTrainingOrder {...formProps}/>;
  }
}
