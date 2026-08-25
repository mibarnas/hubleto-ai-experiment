import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import TableApplicants from './TableApplicants';

export interface FormTrainingDateProps extends FormExtendedProps { }
export interface FormTrainingDateState extends FormExtendedState {
  isSending: boolean,
}

export default class FormTrainingDate<P, S> extends FormExtended<FormTrainingDateProps, FormTrainingDateState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-calendar-days',
    model: 'Hubleto/App/Custom/Trainings/Models/TrainingDate',
  }

  props: FormTrainingDateProps;
  state: FormTrainingDateState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormTrainingDate';

  constructor(props: FormTrainingDateProps) {
    super(props);
    this.state = {
      ...this.getStateFromProps(props),
      isSending: false,
    };
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Date')}</b> },
      { uid: 'applicants', title: this.translate('Applicants') },
    ];
  }

  getRecordFormUrl(): string {
    return 'trainings/dates/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    return <>
      <small>{R.TRAINING?.name ?? this.translate('Training date')}</small>
      <h2>{R.datetime_start ?? this.translate('New date')}</h2>
    </>;
  }

  bulkSend(endpoint: string, successMessage: string) {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isSending: true } as FormTrainingDateState);
    request.post(
      endpoint,
      { idTrainingDate: R.id },
      {},
      (result: any) => {
        this.setState({ isSending: false } as FormTrainingDateState);
        const failed: Array<number> = result.failed ?? [];
        globalThis.hubleto.showDialogWarning(<>
          <div>{successMessage}</div>
          <div>{this.translate('Sent')}: {result.sent ?? 0}</div>
          {failed.length == 0 ? null : <div className='mt-2'>
            <b>{this.translate('Could not be sent for applicants')}:</b> {failed.join(', ')}
          </div>}
        </>, { header: this.translate('Emails sent') });
        this.loadRecord();
      },
      (error: any) => {
        this.setState({ isSending: false } as FormTrainingDateState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('Sending failed.')}</pre>);
      }
    );
  }

  renderBulkButtons(): JSX.Element {
    const R = this.state.record;
    if (!(R.id > 0)) return <div className='badge badge-info'>{this.translate('First save the date.')}</div>;

    return <div className='flex flex-wrap gap-2'>
      <button
        className='btn btn-primary btn-small'
        disabled={this.state.isSending || !R.teams_link}
        onClick={() => this.bulkSend('trainings/api/send-meeting-link', this.translate('The meeting link has been sent to the applicants.'))}
      >
        <span className='icon'><i className='fas fa-video'></i></span>
        <span className='text'>{this.translate('Send meeting link')}</span>
      </button>
      <button
        className='btn btn-primary btn-small'
        disabled={this.state.isSending}
        onClick={() => this.bulkSend('trainings/api/send-questionnaire', this.translate('The questionnaire links have been sent to the applicants.'))}
      >
        <span className='icon'><i className='fas fa-clipboard-question'></i></span>
        <span className='text'>{this.translate('Send questionnaire')}</span>
      </button>
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Date')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_training')}
              {this.inputWrapper('datetime_start')}
              {this.inputWrapper('datetime_end')}
              {this.inputWrapper('id_lecturer')}
              {this.inputWrapper('capacity')}
              {this.inputWrapper('teams_link')}
              {this.inputWrapper('note')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Bulk emails')}</div>
            <div className='card-body'>
              {this.renderBulkButtons()}
              {R.teams_link ? null : <div className='badge badge-info mt-2'>{this.translate('Set a Teams meeting link to enable sending it.')}</div>}
            </div>
          </div>
        </div>;

      case 'applicants':
        return R.id > 0
          ? <TableApplicants
              uid={this.props.uid + '_table_applicants'}
              parentForm={this}
              idTrainingDate={R.id}
              customEndpointParams={{ idTrainingDate: R.id }}
            />
          : <div className='badge badge-info'>{this.translate('First create the date, then you will be prompted to add its applicants.')}</div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
