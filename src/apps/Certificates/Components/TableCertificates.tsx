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
    model: 'Hubleto/App/Custom/Certificates/Models/Certificate',
  }

  props: TableCertificatesProps;
  state: TableCertificatesState;

  translationContext: string = 'Hubleto\\App\\Custom\\Certificates\\Loader';
  translationContextInner: string = 'Components\\TableCertificates';

  constructor(props: TableCertificatesProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getFormModalProps() {
    return { ...super.getFormModalProps(), type: 'right wide' };
  }

  /**
   * `idWorker` / `idTraining` are consumed by Certificate's RecordManager
   * `prepareReadQuery()`, which is what scopes the embedded tables.
   */
  getEndpointParams(): any {
    return {
      ...super.getEndpointParams(),
      idWorker: this.props.idWorker,
      idTraining: this.props.idTraining,
    };
  }

  setRecordFormUrl(id: number) {
    if (this.props.parentForm) return;
    window.history.pushState({}, "", globalThis.hubleto.config.projectUrl + '/certificates/' + (id > 0 ? id : 'add'));
  }

  renderForm(): JSX.Element {
    let formProps: FormCertificateProps = this.getFormProps() as FormCertificateProps;
    formProps.uid = this.props.uid + '_form_certificate';
    return <FormCertificate {...formProps}/>;
  }
}
