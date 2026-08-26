import React from 'react'
import TableExtended, { TableExtendedProps, TableExtendedState } from '@hubleto/react-ui/ext/TableExtended';
import FormCertificate, { FormCertificateProps } from './FormCertificate';

export interface TableCertificatesProps extends TableExtendedProps {
  idWorker?: number,
  idTraining?: number,
}

export interface TableCertificatesState extends TableExtendedState { }

export default class TableCertificates extends TableExtended<TableCertificatesProps, TableCertificatesState> {
  static defaultProps = {
    ...TableExtended.defaultProps,
    model: 'Hubleto/App/Custom/Trainings/Models/Certificate',
  }

  props: TableCertificatesProps;
  state: TableCertificatesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\TableCertificates';

  constructor(props: TableCertificatesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /** Consumed by Certificate's RecordManager prepareReadQuery(). */
  getEndpointParams(): any {
    // Only send filters that are actually set -- spreading an undefined
    // prop would clobber the same key coming from customEndpointParams.
    const params: any = { ...super.getEndpointParams() };
    if (this.props.idWorker !== undefined) params.idWorker = this.props.idWorker;
    if (this.props.idTraining !== undefined) params.idTraining = this.props.idTraining;
    return params;
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/trainings/certificates' + (id > 0 ? '/' + id : ''));
  }

  renderForm(): JSX.Element {
    let formProps: FormCertificateProps = this.getFormProps() as FormCertificateProps;
    formProps.uid = this.props.uid + '_form_certificate';
    return <FormCertificate {...formProps}/>;
  }
}
