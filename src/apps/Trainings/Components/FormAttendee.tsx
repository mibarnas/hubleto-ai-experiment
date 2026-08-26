import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import ModalSimple from '@hubleto/react-ui/core/ModalSimple';

export interface FormAttendeeProps extends FormExtendedProps { }
export interface FormAttendeeState extends FormExtendedState {
  showCertificateForm: boolean,
  isGenerating: boolean,
  certificateInput: any,
}

const CERTIFICATE_FIELDS = [
  { name: 'internal_number', label: 'Internal certificate number', type: 'text' },
  { name: 'date_internal_validity', label: 'Internal validity', type: 'date' },
  { name: 'external_number', label: 'External certificate number', type: 'text' },
  { name: 'date_external_validity', label: 'External validity', type: 'date' },
  { name: 'name_validator', label: 'Accreditation authority', type: 'text' },
  { name: 'date_expiration', label: 'Expires on', type: 'date' },
];

export default class FormAttendee<P, S> extends FormExtended<FormAttendeeProps, FormAttendeeState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-user-graduate',
    model: 'Hubleto/App/Custom/Trainings/Models/Attendee',
  }

  props: FormAttendeeProps;
  state: FormAttendeeState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormAttendee';

  constructor(props: FormAttendeeProps) {
    super(props);
    this.state = {
      ...this.getStateFromProps(props),
      showCertificateForm: false,
      isGenerating: false,
      certificateInput: {},
    };
  }

  getTabsLeft() {
    return [ { uid: 'default', title: <b>{this.translate('Attendee')}</b> } ];
  }

  getRecordFormUrl(): string {
    return this.state.record.id > 0 ? 'trainings/attendees/' + this.state.record.id : '';
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    const name = R.WORKER ? [R.WORKER.first_name, R.WORKER.last_name].filter((p) => p).join(' ') : '';
    return <>
      <small>{this.translate('Attendee')}</small>
      <h2>{name ? name : this.translate('New attendee')}</h2>
    </>;
  }

  generateCertificate() {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isGenerating: true } as FormAttendeeState);
    request.post(
      'trainings/api/generate-certificate',
      { idAttendee: R.id, ...this.state.certificateInput },
      {},
      (result: any) => {
        this.setState({ isGenerating: false, showCertificateForm: false } as FormAttendeeState);
        const unresolved: Array<string> = result.unresolvedPlaceholders ?? [];
        globalThis.hubleto.showDialogWarning(<>
          <div>{this.translate('The certificate has been generated and emailed to the attendee.')}</div>
          {unresolved.length == 0 ? null : <div className='mt-2'>
            <b>{this.translate('Template placeholders left unresolved:')}</b>
            <div>{unresolved.join(', ')}</div>
          </div>}
        </>, { header: this.translate('Certificate generated') });
        this.loadRecord();
      },
      (error: any) => {
        this.setState({ isGenerating: false } as FormAttendeeState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('Failed to generate the certificate.')}</pre>);
      }
    );
  }

  /** The spec asks for a form before generating, so defaults can be overridden. */
  renderCertificateModal(): JSX.Element {
    return <ModalSimple uid={this.props.uid + '_certificate_modal'} isOpen={true} type='center'>
      <div className='card'>
        <div className='card-header'>{this.translate('Certificate details')}</div>
        <div className='card-body'>
          <div className='badge badge-info mb-2'>
            {this.translate('Leave a field empty to use the value configured on the training.')}
          </div>
          {CERTIFICATE_FIELDS.map((field) => <div key={field.name} className='mb-2'>
            <label className='block text-xs text-gray-500'>{this.translate(field.label)}</label>
            <input
              type={field.type}
              className='w-full border border-gray-200 p-1'
              value={this.state.certificateInput[field.name] ?? ''}
              onChange={(e) => this.setState({
                certificateInput: { ...this.state.certificateInput, [field.name]: e.target.value },
              } as FormAttendeeState)}
            />
          </div>)}
          <div className='flex gap-2 mt-3'>
            <button className='btn btn-primary btn-small' disabled={this.state.isGenerating} onClick={() => this.generateCertificate()}>
              <span className='icon'><i className='fas fa-certificate'></i></span>
              <span className='text'>{this.translate('Generate')}</span>
            </button>
            <button className='btn btn-transparent btn-small' onClick={() => this.setState({ showCertificateForm: false } as FormAttendeeState)}>
              <span className='text'>{this.translate('Cancel')}</span>
            </button>
          </div>
        </div>
      </div>
    </ModalSimple>;
  }

  /**
   * The one-time link 404s by design once the questionnaire is submitted, so
   * only offer it while it still works; afterwards link to the stored answers.
   */
  renderQuestionnaireLink(): JSX.Element {
    const R = this.state.record;

    if (R.date_questionnaire_filled) {
      return <div>
        <div className='badge badge-success'>
          {this.translate('Submitted')}: {R.date_questionnaire_filled}
        </div>
        {R.id_questionnaire > 0
          ? <a
              className='btn btn-transparent btn-small mt-1'
              href={globalThis.hubleto.config.projectUrl + '/questionnaires/' + R.id_questionnaire}
            >
              <span className='icon'><i className='fas fa-clipboard-question'></i></span>
              <span className='text'>{this.translate('View answers')}</span>
            </a>
          : null}
        <div className='text-xs text-gray-500 mt-1'>
          {this.translate('The one-time link is no longer valid.')}
        </div>
      </div>;
    }

    if (!R.questionnaire_token) {
      return <div className='badge badge-info'>{this.translate('Save the attendee to generate the link.')}</div>;
    }

    const url = R.url_questionnaire
      ?? (globalThis.hubleto.config.projectUrl + '/training-questionnaire?t=' + R.questionnaire_token);

    return <div>
      <div className='flex gap-2 items-center'>
        <a className='btn btn-transparent btn-small' href={url} target='_blank'>
          <span className='icon'><i className='fas fa-up-right-from-square'></i></span>
          <span className='text break-all'>{url}</span>
        </a>
        <button className='btn btn-transparent btn-small' onClick={() => { navigator.clipboard.writeText(url); }}>
          <span className='icon'><i className='fas fa-copy'></i></span>
          <span className='text'>{this.translate('Copy')}</span>
        </button>
      </div>
      <div className='badge badge-warning'>{this.translate('Not submitted yet')}</div>
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <>
          <div className='flex flex-col md:flex-row gap-2'>
            <div className='flex-1 card'>
              <div className='card-header'>{this.translate('Registration')}</div>
              <div className='card-body'>
                {this.inputWrapper('id_worker')}
                {this.inputWrapper('id_schedule')}
                {this.inputWrapper('id_order')}
                {this.inputWrapper('date_registered')}
                {this.inputWrapper('is_passed')}
                {this.divider(this.translate('Catalog sheet data'))}
                {this.inputWrapper('education_level')}
                {this.inputWrapper('financing_type')}
                {this.inputWrapper('file_last_certificate')}
              </div>
            </div>
            <div className='flex-1 card'>
              <div className='card-header'>{this.translate('Communication')}</div>
              <div className='card-body'>
                {this.inputWrapper('date_meeting_link_sent')}
                {this.inputWrapper('date_questionnaire_sent')}
                {this.divider(this.translate('Questionnaire link'))}
                {this.renderQuestionnaireLink()}
                {this.divider(this.translate('Certificate'))}
                {R.id_certificate > 0
                  ? <a
                      className='btn btn-primary-outline btn-small'
                      target='_blank'
                      href={globalThis.hubleto.config.projectUrl + '/trainings/certificates/download?id=' + R.id_certificate}
                    >
                      <span className='icon'><i className='fas fa-file-pdf'></i></span>
                      <span className='text'>{this.translate('Download certificate')}</span>
                    </a>
                  : <button
                      className='btn btn-primary btn-small'
                      disabled={!(R.id > 0) || !R.is_passed}
                      onClick={() => this.setState({ showCertificateForm: true } as FormAttendeeState)}
                    >
                      <span className='icon'><i className='fas fa-certificate'></i></span>
                      <span className='text'>{this.translate('Generate certificate')}</span>
                    </button>}
                {R.id > 0 && R.is_passed ? null
                  : <div className='badge badge-info mt-2'>{this.translate('A certificate can only be generated once the attendee has passed.')}</div>}
              </div>
            </div>
          </div>
          {this.state.showCertificateForm ? this.renderCertificateModal() : null}
        </>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
