import React from 'react'
import { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import FormAlgo from '../../Trainings/Components/FormAlgo';
import TableAttendees from '../../Trainings/Components/TableAttendees';

const ORDER_TYPE_PRIVATE = 1;
const ORDER_TYPE_COMPANY = 2;

/** Cleared when the order switches to the other type. */
const COMPANY_ONLY_INPUTS = ['id_customer', 'id_contact'];
const PRIVATE_ONLY_INPUTS = ['id_worker'];

export interface FormOrderProps extends FormExtendedProps { }
export interface FormOrderState extends FormExtendedState {
  importPreview?: any,
  isImporting: boolean,
}

export default class FormOrder<P, S> extends FormAlgo<FormOrderProps, FormOrderState> {
  static defaultProps: any = {
    ...FormAlgo.defaultProps,
    icon: 'fas fa-file-invoice',
    model: 'Hubleto/App/Custom/TrainingOrders/Models/Order',
  }

  props: FormOrderProps;
  state: FormOrderState;

  parentApp: string = 'Hubleto/App/Custom/TrainingOrders';

  translationContext: string = 'Hubleto\\App\\Custom\\TrainingOrders\\Loader';
  translationContextInner: string = 'Components\\FormOrder';

  constructor(props: FormOrderProps) {
    super(props);
    this.state = { ...this.getStateFromProps(props), importPreview: null, isImporting: false };
  }

  getMainTab() {
    return { uid: 'default', title: <b>{this.translate('Order')}</b> };
  }

  getRecordFormUrl(): string {
    return 'training-orders/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    const R = this.state.record;
    return <>
      <small>{this.translate('Training order')}</small>
      <h2>{R.identifier ? R.identifier : this.translate('New order')}</h2>
    </>;
  }

  /**
   * The two orderer types are mutually exclusive, so switching between them
   * clears whichever side is being hidden -- otherwise the company that was
   * picked first stays silently attached to what is now a private order, and
   * the hidden input keeps it out of sight until the record is saved.
   *
   * The model clears the same fields on save, so an order can never end up with
   * both an orderer company and an orderer individual whichever way it is
   * edited.
   */
  onOrderTypeChanged(value: any) {
    const toClear = parseInt(value) === ORDER_TYPE_COMPANY ? PRIVATE_ONLY_INPUTS : COMPANY_ONLY_INPUTS;

    const cleared: any = {};
    toClear.forEach((name) => {
      cleared[name] = null;
      // The lookup inputs render from this mirror of the value, so it has to go
      // as well or the cleared field keeps showing the old name.
      cleared['_LOOKUP[' + name + ']'] = '';
    });

    this.updateRecord(cleared);
  }

  importWorkers(commit: boolean) {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isImporting: true } as FormOrderState);
    request.post(
      'training-orders/api/import-order-workers',
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
    const missingColumns: Array<string> = preview.missingRequiredColumns ?? [];

    return <div className='mt-2'>
      <div className='badge badge-success'>
        {preview.commit ? this.translate('Imported') : this.translate('Preview')}
        {' — '}
        {this.translate('existing workers')}: {matched.length}, {this.translate('new workers')}: {created.length}, {this.translate('invalid rows')}: {invalid.length}
      </div>

      {missingColumns.length == 0 ? null : <div className='badge badge-danger mt-1'>
        {this.translate('Columns missing from the file')}: {missingColumns.join(', ')}
      </div>}

      {unmapped.length == 0 ? null : <div className='badge badge-warning mt-1'>
        {this.translate('Columns that are not part of the template and were ignored')}: {unmapped.join(', ')}
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

      {invalid.length == 0 ? null : <div className='mt-2'>
        <div className='badge badge-danger'>{this.translate('Rows that could not be imported')}: {invalid.length}</div>
        <ul className='list-disc pl-4 text-sm mt-1'>
          {invalid.map((row: any, key: number) => <li key={key}>
            {[row.first_name, row.last_name, row.email].filter((p) => p).join(' ') || this.translate('(empty row)')}
            {' — '}
            {(row.problems ?? []).join('; ')}
          </li>)}
        </ul>
      </div>}
    </div>;
  }

  renderPricing(): JSX.Element {
    const R = this.state.record;

    return <table className='w-full text-sm'>
      <tbody>
        <tr className='border-b border-gray-100'>
          <td className='py-1'>{this.translate('Price per person')}</td>
          <td className='py-1 text-right'>{R.virt_price ?? 0}</td>
        </tr>
        <tr className='border-b border-gray-100'>
          <td className='py-1'>{this.translate('Number of workers')}</td>
          <td className='py-1 text-right'>{R.virt_number_of_workers ?? 0}</td>
        </tr>
        <tr>
          <td className='py-1 font-bold'>{this.translate('Total price')}</td>
          <td className='py-1 text-right font-bold'>{R.virt_total_price ?? 0}</td>
        </tr>
      </tbody>
    </table>;
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
                {this.inputWrapper('order_type', { onChange: (input: any, value: any) => this.onOrderTypeChanged(value) })}
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
                {this.inputWrapper('id_currency')}
                {this.inputWrapper('note')}
              </div>
            </div>
            <div className='flex-1 card'>
              <div className='card-header'>{this.translate('Pricing')}</div>
              <div className='card-body'>
                {this.renderPricing()}
                <div className='text-xs text-gray-500 mt-2'>
                  {this.translate('The price per person comes from the booked training, and the head count from the workers on this order.')}
                </div>

                {orderType == ORDER_TYPE_COMPANY ? <>
                  {this.divider(this.translate('Bulk worker import'))}
                  <a
                    className='btn btn-transparent btn-small mb-2'
                    target='_blank'
                    href={globalThis.hubleto.config.projectUrl + '/training-orders/workers-template'}
                  >
                    <span className='icon'><i className='fas fa-file-arrow-down'></i></span>
                    <span className='text'>{this.translate('Download import template')}</span>
                  </a>
                  {this.inputWrapper('file_workers')}
                  <div className='text-xs text-gray-500 mb-2'>
                    {this.translate('The file has to follow the import template: Meno, Priezvisko and E-mail are required, the remaining columns are optional.')}
                  </div>
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
