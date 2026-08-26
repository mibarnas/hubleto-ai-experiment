import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';

export interface FormCertificateProps extends FormExtendedProps { }
export interface FormCertificateState extends FormExtendedState { }

export default class FormCertificate<P, S> extends FormExtended<FormCertificateProps, FormCertificateState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-certificate',
    model: 'Hubleto/App/Custom/Trainings/Models/Certificate',
  }

  props: FormCertificateProps;
  state: FormCertificateState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormCertificate';

  constructor(props: FormCertificateProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getTabsLeft() {
    return [ { uid: 'default', title: <b>{this.translate('Certificate')}</b> } ];
  }

  getRecordFormUrl(): string {
    return this.state.record.id > 0 ? 'trainings/certificates/' + this.state.record.id : '';
  }

  renderTitle(): JSX.Element {
    return <>
      <small>{this.translate('Certificate')}</small>
      <h2>{this.state.record.internal_number ?? this.translate('New certificate')}</h2>
    </>;
  }

  /** upload/ is not directly reachable, so downloads go through the controller. */
  renderDownloads(): JSX.Element {
    const R = this.state.record;
    if (!R.file) return <div className="badge badge-info">{this.translate('No file has been generated yet.')}</div>;

    const base = globalThis.hubleto.config.projectUrl + '/trainings/certificates/download?id=' + R.id;
    return <div className='flex flex-wrap gap-2'>
      <a className="btn btn-primary-outline btn-small" target="_blank" href={base}>
        <span className="icon"><i className="fas fa-file-pdf"></i></span>
        <span className="text">{this.translate('Download PDF')}</span>
      </a>
      {R.file_docx ? <a className="btn btn-transparent btn-small" target="_blank" href={base + '&docx=1'}>
        <span className="icon"><i className="fas fa-file-word"></i></span>
        <span className="text">{this.translate('Source .docx')}</span>
      </a> : null}
    </div>;
  }

  renderTab(tabUid: string) {
    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Certificate')}</div>
            <div className='card-body'>
              {this.inputWrapper('internal_number')}
              {this.inputWrapper('date_internal_validity')}
              {this.inputWrapper('date_expiration')}
              {this.inputWrapper('date_sent')}
              {this.divider(this.translate('Files'))}
              {this.renderDownloads()}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Context')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_worker')}
              {this.inputWrapper('id_training')}
              {this.inputWrapper('id_document')}
              {this.divider(this.translate('External accreditation'))}
              {this.inputWrapper('name_validator')}
              {this.inputWrapper('external_number')}
              {this.inputWrapper('date_external_validity')}
            </div>
          </div>
        </div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
