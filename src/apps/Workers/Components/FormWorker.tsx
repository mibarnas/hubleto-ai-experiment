import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import TableApplicants from '../../Trainings/Components/TableApplicants';
import TableTrainingOrders from '../../Trainings/Components/TableTrainingOrders';
import TableCertificates from '../../Certificates/Components/TableCertificates';

export interface FormWorkerProps extends FormExtendedProps { }
export interface FormWorkerState extends FormExtendedState { }

export default class FormWorker<P, S> extends FormExtended<FormWorkerProps, FormWorkerState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
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

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Worker')}</b> },
      { uid: 'trainings', title: this.translate('Trainings attended') },
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

  renderNotSavedYet(): JSX.Element {
    return <div className='badge badge-info'>{this.translate('First save the worker.')}</div>;
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
              {this.inputWrapper('email')}
              {this.inputWrapper('phone')}
              {this.divider(this.translate('Address'))}
              {this.inputWrapper('street')}
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
              {this.inputWrapper('workplace_street')}
              {this.inputWrapper('workplace_city')}
              {this.inputWrapper('workplace_zip')}
              {this.divider(this.translate('Retraining'))}
              {this.inputWrapper('date_next_retraining')}
              {this.inputWrapper('id_next_retraining_training')}
              {this.divider(this.translate('Other'))}
              {this.inputWrapper('note')}
              {this.inputWrapper('id_owner')}
              {this.inputWrapper('id_manager')}
            </div>
          </div>
        </div>;

      case 'trainings':
        return R.id > 0
          ? <TableApplicants
              uid={this.props.uid + '_table_applicants'}
              parentForm={this}
              idWorker={R.id}
              customEndpointParams={{ idWorker: R.id }}
            />
          : this.renderNotSavedYet();

      case 'certificates':
        return R.id > 0
          ? <TableCertificates
              uid={this.props.uid + '_table_certificates'}
              parentForm={this}
              idWorker={R.id}
              customEndpointParams={{ idWorker: R.id }}
            />
          : this.renderNotSavedYet();

      case 'orders':
        return R.id > 0
          ? <TableTrainingOrders
              uid={this.props.uid + '_table_orders'}
              parentForm={this}
              idWorker={R.id}
              customEndpointParams={{ idWorker: R.id }}
            />
          : this.renderNotSavedYet();

      default:
        return super.renderTab(tabUid);
    }
  }
}
