import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import HubletoChart from '@hubleto/react-ui/core/Chart';
import request from '@hubleto/react-ui/core/Request';
import TableTrainingDates from './TableTrainingDates';

export interface FormTrainingProps extends FormExtendedProps { }
export interface FormTrainingState extends FormExtendedState {
  statistics?: any,
  statisticsLoadedForId?: number,
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
      statisticsLoadedForId: 0,
    };
  }

  getTabsLeft() {
    return [
      { uid: 'default', title: <b>{this.translate('Training')}</b> },
      { uid: 'dates', title: this.translate('Dates') },
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

  /** Loads once per record; the tab is re-rendered on every tab switch. */
  loadStatistics() {
    const R = this.state.record;
    if ((this.state.activeTabUid ?? '').split('.')[0] != 'statistics') return;
    if (!(R.id > 0)) return;
    if (this.state.statisticsLoadedForId == R.id) return;

    request.get(
      'trainings/api/statistics',
      { idTraining: R.id },
      (result: any) => {
        this.setState({ statistics: result, statisticsLoadedForId: R.id } as FormTrainingState);
      }
    );
  }

  /** `certificate_template_params` is filled by Training::refreshTemplateParams() on upload. */
  renderTemplateParams(): JSX.Element {
    const raw = this.state.record.certificate_template_params;
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

  /** Rating spread for one question -- two questions can share a mean and differ wildly. */
  renderDistribution(code: string): JSX.Element {
    const counts: any = this.state.statistics?.distribution?.[code];
    if (!counts) return <></>;

    const total = [1, 2, 3, 4, 5].reduce((sum, r) => sum + (counts[r] ?? 0), 0);
    if (total == 0) return <span className='text-gray-400'>-</span>;

    const shades = ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e'];

    return <span className='flex h-3 w-full' title={[1, 2, 3, 4, 5].map((r) => r + ': ' + (counts[r] ?? 0)).join(', ')}>
      {[1, 2, 3, 4, 5].map((r) => {
        const share = (counts[r] ?? 0) * 100 / total;
        return share == 0 ? null : <span key={r} style={{ width: share + '%', backgroundColor: shades[r - 1] }}></span>;
      })}
    </span>;
  }

  renderStatistics(): JSX.Element {
    const R = this.state.record;
    if (!(R.id > 0)) return <div className='badge badge-info'>{this.translate('First save the training.')}</div>;

    const stats = this.state.statistics;
    if (!stats) return <div className='badge badge-info'>{this.translate('Loading statistics...')}</div>;

    if (!stats.responseCount) {
      return <div className='badge badge-info'>{this.translate('No questionnaire has been filled in for this training yet.')}</div>;
    }

    const labels: Array<string> = stats.data?.labels ?? [];
    const values: Array<number> = stats.data?.values ?? [];
    const codes: Array<string> = Object.keys(stats.averages ?? {});
    const trend: any = stats.trend ?? { labels: [], values: [] };
    const freeText: any = stats.freeText ?? {};

    return <div className='flex flex-col gap-2'>
      <div className='card'>
        <div className='card-header flex justify-between items-center'>
          <span>{this.translate('Average rating per question')} ({this.translate('responses')}: {stats.responseCount})</span>
          <a
            className='btn btn-transparent btn-small'
            target='_blank'
            href={globalThis.hubleto.config.projectUrl + '/trainings/statistics/export-csv?idTraining=' + R.id}
          >
            <span className='icon'><i className='fas fa-file-csv'></i></span>
            <span className='text'>{this.translate('Export CSV')}</span>
          </a>
        </div>
        <div className='card-body'>
          <HubletoChart type='bar' data={stats.data} legend={{ display: false }}/>
          <table className='w-full mt-4 text-sm'>
            <thead><tr>
              <th className='text-left'>{this.translate('Question')}</th>
              <th className='text-right'>{this.translate('Average')}</th>
              <th className='text-right w-40'>{this.translate('Ratings 1-5')}</th>
            </tr></thead>
            <tbody>
              {labels.map((label: string, key: number) => <tr key={key} className='border-b border-gray-100'>
                <td className='py-1'>{label}</td>
                <td className='py-1 text-right font-bold'>{values[key]}</td>
                <td className='py-1'>{this.renderDistribution(codes[key])}</td>
              </tr>)}
            </tbody>
          </table>
        </div>
      </div>
      {trend.labels.length < 2 ? null : <div className='card'>
        <div className='card-header'>{this.translate('Overall satisfaction per date')}</div>
        <div className='card-body'>
          <HubletoChart type='line' data={{
            labels: trend.labels,
            datasets: [{
              label: this.translate('Overall satisfaction'),
              data: trend.values,
              borderColor: 'rgb(34, 197, 94)',
              backgroundColor: 'rgb(34, 197, 94)',
            }],
          }} options={{ scales: { y: { beginAtZero: true, max: 5 } } }}/>
        </div>
      </div>}
      <div className='card'>
        <div className='card-header'>{this.translate('Comments')}</div>
        <div className='card-body flex flex-col gap-2'>
          {Object.keys(freeText).map((code: string) => <div key={code}>
            <div className='text-xs text-gray-500'>{code}</div>
            {(freeText[code] ?? []).length == 0
              ? <div className='text-gray-400'>{this.translate('No answers')}</div>
              : <ul className='list-disc pl-4'>{(freeText[code] ?? []).map((answer: string, key: number) => <li key={key}>{answer}</li>)}</ul>
            }
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
              {this.inputWrapper('training_number')}
              {this.inputWrapper('id_company')}
              {this.inputWrapper('price_per_person')}
              {this.inputWrapper('id_currency')}
              {this.inputWrapper('retraining_interval_years')}
              {this.inputWrapper('is_active')}
              {this.inputWrapper('description')}
              {this.divider(this.translate('Responsibility'))}
              {this.inputWrapper('id_owner')}
              {this.inputWrapper('id_manager')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header'>{this.translate('Certificate template')}</div>
            <div className='card-body'>
              {this.inputWrapper('certificate_template')}
              {this.divider(this.translate('Discovered placeholders'))}
              {this.renderTemplateParams()}
              {this.divider(this.translate('RÚVZ accreditation defaults'))}
              {this.inputWrapper('ruvz_name')}
              {this.inputWrapper('ruvz_certificate_number')}
              {this.inputWrapper('ruvz_date_issued')}
            </div>
          </div>
        </div>;

      case 'dates':
        return R.id > 0
          ? <TableTrainingDates
              uid={this.props.uid + '_table_dates'}
              parentForm={this}
              idTraining={R.id}
              customEndpointParams={{ idTraining: R.id }}
            />
          : <div className='badge badge-info'>{this.translate('First create the training, then you will be prompted to add its dates.')}</div>;

      case 'statistics':
        return this.renderStatistics();

      default:
        return super.renderTab(tabUid);
    }
  }
}
