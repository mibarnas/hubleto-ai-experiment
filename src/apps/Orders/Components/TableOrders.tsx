import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormOrder, { FormOrderProps } from './FormOrder';

export interface TableOrdersProps extends TableExtendedProps {
  idWorker?: number,
  idCustomer?: number,
  idSchedule?: number,
}

export interface TableOrdersState extends TableExtendedState { }

export default class TableOrders extends TableExtended<TableOrdersProps, TableOrdersState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Orders/Models/Order',
  }

  props: TableOrdersProps;
  state: TableOrdersState;

  translationContext: string = 'Hubleto\\App\\Custom\\Orders\\Loader';
  translationContextInner: string = 'Components\\TableOrders';

  constructor(props: TableOrdersProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by Order's RecordManager prepareReadQuery(). */
  getEndpointParams(): any {
    // Only send filters that are actually set -- spreading an undefined
    // prop would clobber the same key coming from customEndpointParams.
    const params: any = { ...super.getEndpointParams() };
    if (this.props.idWorker !== undefined) params.idWorker = this.props.idWorker;
    if (this.props.idCustomer !== undefined) params.idCustomer = this.props.idCustomer;
    if (this.props.idSchedule !== undefined) params.idSchedule = this.props.idSchedule;
    return params;
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/orders/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormOrderProps = this.getFormProps() as FormOrderProps;
    formProps.uid = this.props.uid + '_form_order';

    let defaults: any = {};
    if (this.props.idWorker) defaults.id_worker = this.props.idWorker;
    if (this.props.idCustomer) defaults.id_customer = this.props.idCustomer;
    if (this.props.idSchedule) defaults.id_schedule = this.props.idSchedule;

    formProps.description = formProps.description ?? {};
    formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, ...defaults };

    return <FormOrder {...formProps}/>;
  }
}
