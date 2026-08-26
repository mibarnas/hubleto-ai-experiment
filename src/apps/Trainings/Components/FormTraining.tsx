import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import RatingsChart from './RatingsChart';
import request from '@hubleto/react-ui/core/Request';
import TableSchedules from './TableSchedules';

export interface FormTrainingProps extends FormExtendedProps { }
export interface FormTrainingState extends FormExtendedState {
  statistics?: any,
  statisticsKey?: string,
  dateFrom: string,
  dateTo: string,
}

export default class FormTraining<P, S> extends FormExtended<FormTrainingProps, FormTrainingState> {
  static defaultProps: any = {
    ...FormExtended.defaultProps,
    icon: 'fas fa-chalkboard-user',
    model: 'Hubleto/App/Custom/Trainings/Models/Training',
  }

  props: FormTrainingProps;
  state: FormTrainingState;

  parentApp: string = 'Hubleto/App/Custom/Trainings';

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\FormTraining';

  constructor(props: FormTrainingProps) {
    super(props);
    this.state = {
      ...this.getStateFromProps(props),
      statistics: null,
      statisticsKey: '',
      dateFrom: '',
      dateTo: '',
    };
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Training')}</b> },
      { uid: 'schedules', title: this.translate('Schedules') },
      { uid: 'statistics', title: this.translate('Statistics') },
    ];
  }

  getRecordFormUrl(): string {
    return 'trainings/' + (this.state.record.id > 0 ? this.state.record.id : 'add');
  }

  renderTitle(): JSX.Element {
    return <>
      <small>{this.translate('Training')}</small>
      <h2>{this.state.record.name ? this.state.record.name : this.translate('New training')}</h2>
    </>;
  }

  onTabChange() {
    super.onTabChange();
    this.loadStatistics();
  }

  /** Reloads whenever the record or the date interval changes. */
  loadStatistics(force: boolean = false) {
    const R = this.state.record;
    if ((this.state.activeTabUid ?? '').split('.')[0] != 'statistics') return;
    if (!(R.id > 0)) return;

    const key = [R.id, this.state.dateFrom, this.state.dateTo].join('|');
    if (!force && this.state.statisticsKey == key) return;

    request.get(
      'trainings/api/get-statistics',
      { idTraining: R.id, dateFrom: this.state.dateFrom, dateTo: this.state.dateTo },
      (result: any) => {
        this.setState({ statistics: result, statisticsKey: key } as FormTrainingState);
      }
    );
  }

  /** template_params is filled by Training::refreshTemplateParams() on upload. */
  renderTemplateParams(): JSX.Element {
    const raw = this.state.record.template_params;
    let params: Array<string> = [];

    try {
      params = typeof raw == 'string' ? JSON.parse(raw) : (raw ?? []);
    } catch (e) {
      params = [];
    }

    if (!Array.isArray(params) || params.length == 0) {
      return <div className='badge badge-info'>{this.translate('Upload a .docx template to discover its placeholders.')}</div>;
    }

    return <div className='flex flex-wrap gap-1'>
      {params.map((param: string, key: number) => <span key={key} className='badge badge-secondary'>{'<' + param + '>'}</span>)}
    </div>;
  }

  renderStatistics(): JSX.Element {
    const R = this.state.record;
    if (!(R.id > 0)) return <div className='badge badge-info'>{this.translate('First save the training.')}</div>;

    const stats = this.state.statistics;
    const csvUrl = globalThis.hubleto.config.projectUrl + '/trainings/statistics/export-csv?idTraining=' + R.id
      + '&dateFrom=' + encodeURIComponent(this.state.dateFrom)
      + '&dateTo=' + encodeURIComponent(this.state.dateTo);

    const filter = <div className='card'>
      <div className='card-header flex justify-between items-center flex-wrap gap-2'>
        <span>{this.translate('Filter by date filled')}</span>
        <a className='btn btn-transparent btn-small' target='_blank' href={csvUrl}>
          <span className='icon'><i className='fas fa-file-csv'></i></span>
          <span className='text'>{this.translate('Export CSV')}</span>
        </a>
      </div>
      <div className='card-body flex flex-wrap gap-2 items-end'>
        <div>
          <label className='block text-xs text-gray-500'>{this.translate('From')}</label>
          <input type='date' className='border border-gray-200 p-1' value={this.state.dateFrom}
            onChange={(e) => this.setState({ dateFrom: e.target.value } as FormTrainingState, () => this.loadStatistics(true))}/>
        </div>
        <div>
          <label className='block text-xs text-gray-500'>{this.translate('To')}</label>
          <input type='date' className='border border-gray-200 p-1' value={this.state.dateTo}
            onChange={(e) => this.setState({ dateTo: e.target.value } as FormTrainingState, () => this.loadStatistics(true))}/>
        </div>
      </div>
    </div>;

    if (!stats) return <>{filter}<div className='badge badge-info mt-2'>{this.translate('Loading statistics...')}</div></>;

    if (!stats.responseCount) {
      return <>{filter}<div className='badge badge-info mt-2'>{this.translate('No questionnaire matches this filter.')}</div></>;
    }

    const labels: Array<string> = stats.data?.labels ?? [];
    const values: Array<number> = stats.data?.values ?? [];
    const distribution: any = stats.distribution ?? {};
    const freeText: any = stats.freeText ?? {};

    return <div className='flex flex-col gap-2'>
      {filter}
      <div className='card'>
        <div className='card-header'>
          {this.translate('Average rating per question')} ({this.translate('responses')}: {stats.responseCount})
        </div>
        <div className='card-body'>
          <div style={{ height: '220px' }}>
            <RatingsChart labels={labels} values={values} colors={stats.data?.colors}/>
          </div>
          <table className='w-full mt-4 text-sm'>
            <thead><tr>
              <th className='text-left'>{this.translate('Question')}</th>
              <th className='text-right'>{this.translate('Average')}</th>
              <th className='text-right'>1-5</th>
            </tr></thead>
            <tbody>
              {labels.map((label: string, key: number) => {
                const code = Object.keys(distribution)[key];
                const counts = distribution[code] ?? {};
                return <tr key={key} className='border-b border-gray-100'>
                  <td className='py-1'>{label}</td>
                  <td className='py-1 text-right font-bold'>{values[key]}</td>
                  <td className='py-1 text-right text-gray-500'>
                    {[1, 2, 3, 4, 5].map((n) => (counts[n] ?? 0)).join(' / ')}
                  </td>
                </tr>;
              })}
            </tbody>
          </table>
        </div>
      </div>
      <div className='card'>
        <div className='card-header'>{this.translate('Comments')}</div>
        <div className='card-body flex flex-col gap-2'>
          {Object.keys(freeText).map((code: string) => <div key={code}>
            <div className='text-xs text-gray-500'>{code}</div>
            {(freeText[code] ?? []).length == 0
              ? <div className='text-gray-400'>{this.translate('No answers')}</div>
              : <ul className='list-disc pl-4'>{(freeText[code] ?? []).map((answer: string, key: number) => <li key={key}>{answer}</li>)}</ul>}
          </div>)}
        </div>
      </div>
    </div>;
  }

  renderTab(tabUid: string) {
    const R = this.state.record;

    switch (tabUid) {
      case 'default':
        return <div className='flex flex-col md:flex-row gap-2'>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Training')}</div>
            <div className='card-body'>
              {this.inputWrapper('name')}
              {this.inputWrapper('number')}
              {this.inputWrapper('id_company')}
              {this.inputWrapper('price')}
              {this.inputWrapper('id_currency')}
              {this.inputWrapper('interval')}
              {this.inputWrapper('is_active')}
              {this.inputWrapper('description')}
              {this.divider(this.translate('Responsibility'))}
              {this.inputWrapper('id_owner')}
              {this.inputWrapper('id_manager')}
              {this.inputWrapper('shared_with')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Certificate template')}</div>
            <div className='card-body'>
              {this.inputWrapper('template')}
              {this.divider(this.translate('Discovered placeholders'))}
              {this.renderTemplateParams()}
              {this.divider(this.translate('Accreditation defaults'))}
              {this.inputWrapper('name_validator')}
              {this.inputWrapper('external_number')}
              {this.inputWrapper('date_external_issued')}
            </div>
          </div>
        </div>;

      case 'schedules':
        return R.id > 0
          ? <TableSchedules
              uid={this.props.uid + '_table_schedules'}
              parentForm={this}
              idTraining={R.id}
              customEndpointParams={{ idTraining: R.id }}
            />
          : <div className='badge badge-info'>{this.translate('First create the training, then you will be prompted to add its schedules.')}</div>;

      case 'statistics':
        return this.renderStatistics();

      default:
        return super.renderTab(tabUid);
    }
  }
}
