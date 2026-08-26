import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormWorker, { FormWorkerProps } from './FormWorker';

export interface TableWorkersProps extends TableExtendedProps {
  idCustomer?: number,
}

export interface TableWorkersState extends TableExtendedState { }

export default class TableWorkers extends TableExtended<TableWorkersProps, TableWorkersState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Workers/Models/Worker',
  }

  props: TableWorkersProps;
  state: TableWorkersState;

  translationContext: string = 'Hubleto\\App\\Custom\\Workers\\Loader';
  translationContextInner: string = 'Components\\TableWorkers';

  constructor(props: TableWorkersProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  getCsvImportEndpointParams(): any {
    return { model: this.props.model };
  }

  /** Consumed by Worker's RecordManager `prepareReadQuery()`. */
  getEndpointParams(): any {
    // Only send filters that are actually set -- spreading an undefined
    // prop would clobber the same key coming from customEndpointParams.
    const params: any = { ...super.getEndpointParams() };
    if (this.props.idCustomer !== undefined) params.idCustomer = this.props.idCustomer;
    return params;
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/workers/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormWorkerProps = this.getFormProps() as FormWorkerProps;
    formProps.uid = this.props.uid + '_form_worker';

    if (this.props.idCustomer) {
      formProps.description = formProps.description ?? {};
      formProps.description.defaultValues = { ...formProps.description.defaultValues ?? {}, id_customer: this.props.idCustomer };
    }

    return <FormWorker {...formProps}/>;
  }
}
