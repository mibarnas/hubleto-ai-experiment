import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import TableAttendees from '../../Trainings/Components/TableAttendees';

const ORDER_TYPE_PRIVATE = 1;
const ORDER_TYPE_COMPANY = 2;

export interface FormOrderProps extends FormExtendedProps { }
export interface FormOrderState extends FormExtendedState {
  importPreview?: any,
  isImporting: boolean,
}

export default class FormOrder<P, S> extends FormExtended<FormOrderProps, FormOrderState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-file-invoice',
    model: 'Hubleto/App/Custom/Orders/Models/Order',
  }

  props: FormOrderProps;
  state: FormOrderState;

  parentApp: string = 'Hubleto/App/Custom/Orders';

  translationContext: string = 'Hubleto\\App\\Custom\\Orders\\Loader';
  translationContextInner: string = 'Components\\FormOrder';

  constructor(props: FormOrderProps) {
    super(props);
    this.state = { ...this.getStateFromProps(props), importPreview: null, isImporting: false };
  }

  /** The spec asks for the order detail to have no tabs. */
  getTabsLeft() {
    return [ { uid: 'default', title: <b>{this.translate('Order')}</b> } ];
  }

  getRecordFormUrl(): string {
    return 'orders/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    return <>
      <small>{this.translate('Training order')}</small>
      <h2>{R.identifier ? R.identifier : this.translate('New order')}</h2>
    </>;
  }

  recalculate() {
    const R = this.state.record;
    if (!(R.id > 0)) return;
    request.post('orders/api/recalculate-order', { idOrder: R.id }, {}, () => { this.loadRecord(); });
  }

  importWorkers(commit: boolean) {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isImporting: true } as FormOrderState);
    request.post(
      'orders/api/import-order-workers',
      { idOrder: R.id, commit: commit },
      {},
      (result: any) => {
        this.setState({ isImporting: false, importPreview: result } as FormOrderState);
        if (commit) this.loadRecord();
      },
      (error: any) => {
        this.setState({ isImporting: false } as FormOrderState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('Import failed.')}</pre>);
      }
    );
  }

  renderImportPreview(): JSX.Element {
    const preview = this.state.importPreview;
    if (!preview) return <></>;

    const matched: Array<any> = preview.matched ?? [];
    const created: Array<any> = preview.new ?? [];
    const invalid: Array<any> = preview.invalid ?? [];
    const unmapped: Array<string> = preview.unmappedColumns ?? [];

    return <div className='mt-2'>
      <div className='badge badge-success'>
        {preview.commit ? this.translate('Imported') : this.translate('Preview')}
        {' — '}
        {this.translate('existing workers')}: {matched.length}, {this.translate('new workers')}: {created.length}, {this.translate('invalid rows')}: {invalid.length}
      </div>
      {unmapped.length == 0 ? null : <div className='badge badge-warning mt-1'>
        {this.translate('Ignored columns')}: {unmapped.join(', ')}
      </div>}
      {[...matched, ...created].length == 0 ? null : <table className='w-full mt-2 text-sm'>
        <thead><tr>
          <th className='text-left'>{this.translate('Name')}</th>
          <th className='text-left'>{this.translate('Email')}</th>
          <th className='text-left'>{this.translate('Status')}</th>
        </tr></thead>
        <tbody>
          {matched.map((row: any, key: number) => <tr key={'m' + key} className='border-b border-gray-100'>
            <td>{[row.first_name, row.last_name].filter((p) => p).join(' ')}</td>
            <td>{row.email}</td>
            <td>{this.translate('Existing worker')}</td>
          </tr>)}
          {created.map((row: any, key: number) => <tr key={'n' + key} className='border-b border-gray-100'>
            <td>{[row.first_name, row.last_name].filter((p) => p).join(' ')}</td>
            <td>{row.email}</td>
            <td>{this.translate('New worker')}</td>
          </tr>)}
        </tbody>
      </table>}
      {invalid.length == 0 ? null : <div className='badge badge-danger mt-2'>
        {this.translate('Rows without a valid email address were skipped')}: {invalid.length}
      </div>}
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;
    const orderType = parseInt(R.order_type ?? ORDER_TYPE_PRIVATE);

    switch (tabUid) {
      case 'default':
        return <>
          <div className='flex flex-col md:flex-row gap-2'>
            <div className='flex-1 card'>
              <div className='card-header'>{this.translate('Orderer')}</div>
              <div className='card-body'>
                {this.inputWrapper('identifier')}
                {this.inputWrapper('order_type')}
                {/* The form shows different fields depending on the order type. */}
                {orderType == ORDER_TYPE_COMPANY ? <>
                  {this.inputWrapper('id_customer')}
                  {this.inputWrapper('id_contact')}
                </> : <>
                  {this.inputWrapper('id_worker')}
                </>}
                {this.divider(this.translate('Training'))}
                {this.inputWrapper('id_schedule')}
                {this.inputWrapper('date_ordered')}
                {this.inputWrapper('date_paid')}
                {this.inputWrapper('note')}
                {this.divider(this.translate('Responsibility'))}
                {this.inputWrapper('id_owner')}
                {this.inputWrapper('id_manager')}
                {this.inputWrapper('shared_with')}
              </div>
            </div>
            <div className='flex-1 card'>
              <div className='card-header flex justify-between items-center'>
                <span>{this.translate('Pricing')}</span>
                {R.id > 0 ? <button className='btn btn-transparent btn-small' onClick={() => this.recalculate()}>
                  <span className='icon'><i className='fas fa-rotate'></i></span>
                  <span className='text'>{this.translate('Recalculate')}</span>
                </button> : null}
              </div>
              <div className='card-body'>
                {this.inputWrapper('price')}
                {this.inputWrapper('id_currency')}
                {this.inputWrapper('number_of_workers')}
                {this.inputWrapper('total_price')}

                {orderType == ORDER_TYPE_COMPANY ? <>
                  {this.divider(this.translate('Bulk worker import'))}
                  {this.inputWrapper('file_workers')}
                  {R.id > 0 && R.file_workers ? <div className='flex gap-2 mt-2'>
                    <button className='btn btn-transparent btn-small' disabled={this.state.isImporting} onClick={() => this.importWorkers(false)}>
                      <span className='icon'><i className='fas fa-eye'></i></span>
                      <span className='text'>{this.translate('Preview import')}</span>
                    </button>
                    <button className='btn btn-primary btn-small' disabled={this.state.isImporting} onClick={() => this.importWorkers(true)}>
                      <span className='icon'><i className='fas fa-file-import'></i></span>
                      <span className='text'>{this.translate('Import workers')}</span>
                    </button>
                  </div> : <div className='badge badge-info mt-2'>
                    {this.translate('Save the order with an .xlsx attached to import workers from it.')}
                  </div>}
                  {this.renderImportPreview()}
                </> : null}
              </div>
            </div>
          </div>

          {/* No tabs: the workers on the order are listed inline. */}
          <div className='card mt-2'>
            <div className='card-header'>{this.translate('Workers on this order')}</div>
            <div className='card-body'>
              {R.id > 0
                ? <TableAttendees
                    uid={this.props.uid + '_table_attendees'}
                    parentForm={this}
                    idOrder={R.id}
                    idSchedule={R.id_schedule}
                    customEndpointParams={{ idOrder: R.id }}
                  />
                : <div className='badge badge-info'>{this.translate('First create the order, then you will be prompted to add its workers.')}</div>}
            </div>
          </div>
        </>;

      default:
        return super.renderTab(tabUid);
    }
  }
}
