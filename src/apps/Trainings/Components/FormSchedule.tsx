import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import TableAttendees from './TableAttendees';

export interface FormScheduleProps extends FormExtendedProps { }
export interface FormScheduleState extends FormExtendedState {
  isSending: boolean,
}

export default class FormSchedule<P, S> extends FormExtended<FormScheduleProps, FormScheduleState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-calendar-days',
    model: 'Hubleto/App/Custom/Trainings/Models/Schedule',
  }

  props: FormScheduleProps;
  state: FormScheduleState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormSchedule';

  constructor(props: FormScheduleProps) {
    super(props);
    this.state = { ...this.getStateFromProps(props), isSending: false };
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Schedule')}</b> },
      { uid: 'attendees', title: this.translate('Attendees') },
    ];
  }

  getRecordFormUrl(): string {
    return 'trainings/schedules/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    return <>
      <small>{R.TRAINING?.name ?? this.translate('Schedule')}</small>
      <h2>{R.date_start ?? this.translate('New schedule')}</h2>
    </>;
  }

  bulkSend(endpoint: string, successMessage: string) {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isSending: true } as FormScheduleState);
    request.post(
      endpoint,
      { idSchedule: R.id },
      {},
      (result: any) => {
        this.setState({ isSending: false } as FormScheduleState);
        const failed: Array<number> = result.failed ?? [];
        globalThis.hubleto.showDialogWarning(<>
          <div>{successMessage}</div>
          <div>{this.translate('Sent')}: {result.sent ?? 0}</div>
          {failed.length == 0 ? null : <div className='mt-2'>
            <b>{this.translate('Could not be sent for attendees')}:</b> {failed.join(', ')}
          </div>}
        </>, { header: this.translate('Emails sent') });
        this.loadRecord();
      },
      (error: any) => {
        this.setState({ isSending: false } as FormScheduleState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('Sending failed.')}</pre>);
      }
    );
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Schedule')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_training')}
              {this.inputWrapper('date_start')}
              {this.inputWrapper('date_end')}
              {this.inputWrapper('id_lecturer')}
              {this.inputWrapper('capacity')}
              {this.inputWrapper('meeting_link')}
              {this.inputWrapper('note')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Bulk emails')}</div>
            <div className='card-body'>
              {R.id > 0 ? <div className='flex flex-wrap gap-2'>
                <button
                  className='btn btn-primary btn-small'
                  disabled={this.state.isSending || !R.meeting_link}
                  onClick={() => this.bulkSend('trainings/api/send-meeting-link', this.translate('The meeting link has been sent to the attendees.'))}
                >
                  <span className='icon'><i className='fas fa-video'></i></span>
                  <span className='text'>{this.translate('Send meeting link')}</span>
                </button>
                <button
                  className='btn btn-primary btn-small'
                  disabled={this.state.isSending}
                  onClick={() => this.bulkSend('trainings/api/send-questionnaire', this.translate('The questionnaire links have been sent to the attendees.'))}
                >
                  <span className='icon'><i className='fas fa-clipboard-question'></i></span>
                  <span className='text'>{this.translate('Send questionnaire')}</span>
                </button>
              </div> : <div className='badge badge-info'>{this.translate('First save the schedule.')}</div>}
              {R.meeting_link ? null : <div className='badge badge-info mt-2'>{this.translate('Set a meeting link to enable sending it.')}</div>}
            </div>
          </div>
        </div>;

      case 'attendees':
        return R.id > 0
          ? <TableAttendees
              uid={this.props.uid + '_table_attendees'}
              parentForm={this}
              idSchedule={R.id}
              customEndpointParams={{ idSchedule: R.id }}
            />
          : <div className='badge badge-info'>{this.translate('First create the schedule, then you will be prompted to add its attendees.')}</div>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
