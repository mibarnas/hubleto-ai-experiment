import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';

export interface FormCertificateProps extends FormExtendedProps { }
export interface FormCertificateState extends FormExtendedState { }

export default class FormCertificate<P, S> extends FormExtended<FormCertificateProps, FormCertificateState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-certificate',
    model: 'Hubleto/App/Custom/Certificates/Models/Certificate',
  }

  props: FormCertificateProps;
  state: FormCertificateState;

  parentApp: string = 'Hubleto/App/Custom/Certificates';

  translationContext: string = 'Hubleto\\App\\Custom\\Certificates\\Loader';
  translationContextInner: string = 'Components\\FormCertificate';

  constructor(props: FormCertificateProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Certificate')}</b> },
    ];
  }

  getRecordFormUrl(): string {
    return 'certificates/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    return <>
      <small>{this.translate('Certificate')}</small>
      <h2>{this.state.record.certificate_number ? this.state.record.certificate_number : this.translate('New certificate')}</h2>
    </>;
  }

  /** Certificates are produced by `certificates/api/generate`, so the file is a read-only link. */
  renderDownloadLink(): JSX.Element {
    const file = this.state.record.file;
    if (!file) {
      return <div className="badge badge-info">{this.translate('No file has been generated yet.')}</div>;
    }
    return <a
      className="btn btn-primary-outline btn-small"
      target="_blank"
      href={globalThis.hubleto.config.projectUrl + '/certificates/download?id=' + this.state.record.id}
    >
      <span className="icon"><i className="fas fa-file-word"></i></span>
      <span className="text">{this.translate('Download certificate')}</span>
    </a>;
  }

  renderTab(tabUid: string) {
    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Certificate')}</div>
            <div className='card-body'>
              {this.inputWrapper('certificate_number')}
              {this.inputWrapper('date_created')}
              {this.inputWrapper('date_valid_until')}
              {this.inputWrapper('sent_to_applicant_on')}
              {this.divider(this.translate('File'))}
              {this.renderDownloadLink()}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Context')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_applicant')}
              {this.inputWrapper('id_worker')}
              {this.inputWrapper('id_training')}
              {this.inputWrapper('id_document')}
              {this.divider(this.translate('RÚVZ accreditation'))}
              {this.inputWrapper('ruvz_name')}
              {this.inputWrapper('ruvz_certificate_number')}
              {this.inputWrapper('ruvz_date_issued')}
            </div>
          </div>
        </div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
