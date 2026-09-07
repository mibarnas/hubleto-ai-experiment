import React from 'react'
import { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import FormAlgo from '../../Trainings/Components/FormAlgo';
import TableAttendees from '../../Trainings/Components/TableAttendees';
import TableSchedules from '../../Trainings/Components/TableSchedules';
import TableCertificates from '../../Trainings/Components/TableCertificates';
import TableOrders from '../../TrainingOrders/Components/TableOrders';

export interface FormWorkerProps extends FormExtendedProps { }
export interface FormWorkerState extends FormExtendedState { }

export default class FormWorker<P, S> extends FormAlgo<FormWorkerProps, FormWorkerState> {
  static defaultProps: any = {
    ...FormAlgo.defaultProps,
    icon: 'fas fa-user-tie',
    model: 'Hubleto/App/Custom/Workers/Models/Worker',
  }

  props: FormWorkerProps;
  state: FormWorkerState;

  parentApp: string = 'Hubleto/App/Custom/Workers';

  translationContext: string = 'Hubleto\\App\\Custom\\Workers\\Loader';
  translationContextInner: string = 'Components\\FormWorker';

  constructor(props: FormWorkerProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  getMainTab() {
    return { uid: 'default', title: <b>{this.translate('Worker')}</b> };
  }

  getRelatedTabs() {
    return [
      { uid: 'trainings', title: this.translate('Trainings') },
      { uid: 'schedules', title: this.translate('Schedules') },
      { uid: 'certificates', title: this.translate('Certificates') },
      { uid: 'orders', title: this.translate('Orders') },
    ];
  }

  getRecordFormUrl(): string {
    return 'workers/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    const name = [R.title_before, R.first_name, R.last_name, R.title_after].filter((p) => p).join(' ');
    return <>
      <small>{this.translate('Worker')}</small>
      <h2>{name ? name : this.translate('New worker')}</h2>
    </>;
  }

  /**
   * Read straight from the worker's certificates every time the form is
   * opened, so it cannot disagree with the Certificates tab below it.
   */
  renderNextRetraining(): JSX.Element {
    const R = this.state.record;

    if (!R.virt_date_next_retraining) {
      return <div className='badge badge-info'>{this.translate('This worker has no certificate with an expiry date yet.')}</div>;
    }

    const expiresOn = new Date(R.virt_date_next_retraining);
    const isOverdue = expiresOn.getTime() < Date.now();

    return <div className='flex flex-col gap-1'>
      <div className={'badge ' + (isOverdue ? 'badge-danger' : 'badge-success')}>
        {isOverdue ? this.translate('Retraining overdue since') : this.translate('Retrain by')}
        {': '}
        {R.virt_date_next_retraining}
      </div>
      {R.virt_next_retraining_training
        ? <div className='text-sm'>{this.translate('Training')}: <b>{R.virt_next_retraining_training}</b></div>
        : null}
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Personal details')}</div>
            <div className='card-body'>
              {this.inputWrapper('title_before')}
              {this.inputWrapper('first_name')}
              {this.inputWrapper('last_name')}
              {this.inputWrapper('title_after')}
              {this.inputWrapper('gender')}
              {this.inputWrapper('birth_number')}
              {this.inputWrapper('email')}
              {this.inputWrapper('phone')}
              {this.divider(this.translate('Address'))}
              {this.inputWrapper('address')}
              {this.inputWrapper('city')}
              {this.inputWrapper('zip')}
              {this.inputWrapper('id_country')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Employment')}</div>
            <div className='card-body'>
              {this.inputWrapper('id_customer')}
              {this.inputWrapper('workplace_name')}
              {this.inputWrapper('workplace_address')}
              {this.inputWrapper('workplace_city')}
              {this.inputWrapper('workplace_zip')}
              {this.divider(this.translate('Next retraining'))}
              {this.renderNextRetraining()}
              {this.divider(this.translate('Other'))}
              {this.inputWrapper('note')}
              {this.inputWrapper('id_owner')}
              {this.inputWrapper('id_manager')}
            </div>
          </div>
        </div>;

      case 'trainings':
        return <TableAttendees uid={this.props.uid + '_table_attendees'} parentForm={this} idWorker={R.id} customEndpointParams={{ idWorker: R.id }}/>;

      case 'schedules':
        return <TableSchedules uid={this.props.uid + '_table_schedules'} parentForm={this} idWorker={R.id} readonly={true} customEndpointParams={{ idWorker: R.id }}/>;

      case 'certificates':
        return <TableCertificates uid={this.props.uid + '_table_certificates'} parentForm={this} idWorker={R.id} customEndpointParams={{ idWorker: R.id }}/>;

      case 'orders':
        return <TableOrders uid={this.props.uid + '_table_orders'} parentForm={this} idWorker={R.id} customEndpointParams={{ idWorker: R.id }}/>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
