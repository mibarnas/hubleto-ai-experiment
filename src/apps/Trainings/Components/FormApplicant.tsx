import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import TableQuestionnaireAnswers from './TableQuestionnaireAnswers';

export interface FormApplicantProps extends FormExtendedProps { }
export interface FormApplicantState extends FormExtendedState {
  isGeneratingCertificate: boolean,
}

export default class FormApplicant<P, S> extends FormExtended<FormApplicantProps, FormApplicantState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-user-graduate',
    model: 'Hubleto/App/Custom/Trainings/Models/Applicant',
  }

  props: FormApplicantProps;
  state: FormApplicantState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormApplicant';

  constructor(props: FormApplicantProps) {
    super(props);
    this.state = {
      ...this.getStateFromProps(props),
      isGeneratingCertificate: false,
    };
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Applicant')}</b> },
      { uid: 'questionnaire', title: this.translate('Questionnaire') },
    ];
  }

  getRecordFormUrl(): string {
    return this.state.record.id > 0 ? 'trainings/applicants/' + this.state.record.id : '';
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    const name = R.WORKER ? [R.WORKER.first_name, R.WORKER.last_name].filter((p) => p).join(' ') : '';
    return <>
      <small>{this.translate('Applicant')}</small>
      <h2>{name ? name : this.translate('New applicant')}</h2>
    </>;
  }

  generateCertificate() {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isGeneratingCertificate: true } as FormApplicantState);
    request.post(
      'certificates/api/generate',
      { idApplicant: R.id },
      {},
      (result: any) => {
        this.setState({ isGeneratingCertificate: false } as FormApplicantState);
        const unresolved: Array<string> = result.unresolvedPlaceholders ?? [];
        globalThis.hubleto.showDialogWarning(<>
          <div>{this.translate('The certificate has been generated and emailed to the applicant.')}</div>
          {unresolved.length == 0 ? null : <div className='mt-2'>
            <b>{this.translate('Template placeholders left unresolved:')}</b>
            <div>{unresolved.join(', ')}</div>
          </div>}
        </>, { header: this.translate('Certificate generated') });
        this.loadRecord();
      },
      (error: any) => {
        this.setState({ isGeneratingCertificate: false } as FormApplicantState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('Failed to generate the certificate.')}</pre>);
      }
    );
  }

  /** One-time links minted by `Applicant::onBeforeCreate()` and mailed by `trainings/api/send-questionnaire`. */
  renderPublicLink(label: string, path: string, token: string, filledOn: string|null): JSX.Element {
    if (!token) return <div className='badge badge-info'>{this.translate('Save the applicant to generate the link.')}</div>;

    const url = globalThis.hubleto.config.projectUrl + '/' + path + '?t=' + token;
    return <div className='mb-2'>
      <div className='text-xs text-gray-500'>{label}</div>
      <div className='flex gap-2 items-center'>
        <a className='btn btn-transparent btn-small' href={url} target='_blank'>
          <span className='icon'><i className='fas fa-up-right-from-square'></i></span>
          <span className='text break-all'>{url}</span>
        </a>
        <button
          className='btn btn-transparent btn-small'
          onClick={() => { navigator.clipboard.writeText(url); }}
        >
          <span className='icon'><i className='fas fa-copy'></i></span>
          <span className='text'>{this.translate('Copy')}</span>
        </button>
      </div>
      {filledOn
        ? <div className='badge badge-success'>{this.translate('Already submitted')}: {filledOn}</div>
        : <div className='badge badge-warning'>{this.translate('Not submitted yet')}</div>
      }
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Registration')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_worker')}
              {this.inputWrapper('id_training_date')}
              {this.inputWrapper('id_order')}
              {this.inputWrapper('date_registered')}
              {this.inputWrapper('is_completed')}
              {this.divider(this.translate('Catalog sheet data'))}
              {this.inputWrapper('education_level')}
              {this.inputWrapper('financing_type')}
              {this.inputWrapper('previous_certificate_file')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Communication')}</div>
            <div className='card-body'>
              {this.inputWrapper('meeting_link_sent_on')}
              {this.inputWrapper('questionnaire_sent_on')}
              {this.divider(this.translate('One-time links'))}
              {this.renderPublicLink(this.translate('Satisfaction questionnaire'), 'training-questionnaire', R.questionnaire_token, R.questionnaire_filled_on)}
              {this.renderPublicLink(this.translate('Catalog sheet'), 'training-catalog-sheet', R.catalog_token, R.catalog_filled_on)}
              {this.divider(this.translate('Certificate'))}
              {R.id > 0 && R.CERTIFICATE
                ? <a
                    className='btn btn-primary-outline btn-small'
                    target='_blank'
                    href={globalThis.hubleto.config.uploadUrl + '/' + R.CERTIFICATE.file}
                  >
                    <span className='icon'><i className='fas fa-file-word'></i></span>
                    <span className='text'>{R.CERTIFICATE.certificate_number}</span>
                  </a>
                : <button
                    className='btn btn-primary btn-small'
                    disabled={!(R.id > 0) || !R.is_completed || this.state.isGeneratingCertificate}
                    onClick={() => this.generateCertificate()}
                  >
                    <span className='icon'><i className='fas fa-certificate'></i></span>
                    <span className='text'>{this.translate('Generate certificate')}</span>
                  </button>
              }
              {R.id > 0 && R.is_completed ? null
                : <div className='badge badge-info mt-2'>{this.translate('A certificate can only be generated once the applicant is marked as completed.')}</div>
              }
            </div>
          </div>
        </div>;

      case 'questionnaire':
        return R.id > 0
          ? <TableQuestionnaireAnswers
              uid={this.props.uid + '_table_questionnaire'}
              parentForm={this}
              idApplicant={R.id}
              customEndpointParams={{ idApplicant: R.id }}
            />
          : <div className='badge badge-info'>{this.translate('First save the applicant.')}</div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
