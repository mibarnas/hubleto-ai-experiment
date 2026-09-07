import React from 'react'
import { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';
import request from '@hubleto/react-ui/core/Request';
import FormAlgo from './FormAlgo';
import TableSchedules from './TableSchedules';

export interface FormTrainingProps extends FormExtendedProps { }
export interface FormTrainingState extends FormExtendedState {
  isCheckingTemplate: boolean,
  available?: Array<any>,
}

export default class FormTraining<P, S> extends FormAlgo<FormTrainingProps, FormTrainingState> {
  static defaultProps: any = {
    ...FormAlgo.defaultProps,
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
      isCheckingTemplate: false,
      available: null,
    };
  }

  getMainTab() {
    return { uid: 'default', title: <b>{this.translate('Training')}</b> };
  }

  getRelatedTabs() {
    return [ { uid: 'schedules', title: this.translate('Schedules') } ];
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

  /** Re-runs the scanner on the server and pulls the fresh analysis back. */
  checkTemplate() {
    const R = this.state.record;
    if (!(R.id > 0)) return;

    this.setState({ isCheckingTemplate: true } as FormTrainingState);
    request.post(
      'trainings/api/check-template',
      { idTraining: R.id },
      {},
      (result: any) => {
        this.setState({
          isCheckingTemplate: false,
          available: result.available ?? [],
          record: { ...this.state.record, template_params: JSON.stringify(result.analysis ?? {}) },
        } as FormTrainingState);
      },
      (error: any) => {
        this.setState({ isCheckingTemplate: false } as FormTrainingState);
        globalThis.hubleto.showDialogDanger(<pre>{error?.message ?? this.translate('The template could not be checked.')}</pre>);
      }
    );
  }

  getTemplateAnalysis(): any {
    const raw = this.state.record.template_params;
    if (!raw) return null;

    try {
      return typeof raw == 'string' ? JSON.parse(raw) : raw;
    } catch (e) {
      return null;
    }
  }

  /**
   * Reports what the uploaded template asks for: which values it uses, which
   * required ones it forgot, and which placeholders match nothing we can
   * supply -- almost always a typo in the document.
   */
  renderTemplateCheck(): JSX.Element {
    const R = this.state.record;

    if (!R.template) {
      return <div className='badge badge-info'>
        {this.translate('Upload a .docx template. Placeholders are written as << value >>.')}
      </div>;
    }

    const analysis = this.getTemplateAnalysis();

    if (!analysis) {
      return <div className='badge badge-info'>
        {this.translate('The template has not been checked yet.')}
      </div>;
    }

    if (analysis.error) {
      return <div className='badge badge-danger'>{analysis.error}</div>;
    }

    const found: Array<string> = analysis.found ?? [];
    const missing: Array<string> = analysis.missingRequired ?? [];
    const unknown: any = analysis.unknown ?? {};
    const unknownKeys: Array<string> = Object.keys(unknown);

    return <div className='flex flex-col gap-2'>
      {missing.length > 0 ? <div className='badge badge-danger'>
        <b>{this.translate('Required values missing from the template')}:</b>{' '}
        {missing.map((key) => '<< ' + key + ' >>').join(', ')}
      </div> : null}

      {unknownKeys.length > 0 ? <div className='badge badge-warning'>
        <b>{this.translate('Placeholders that cannot be filled in')}:</b>{' '}
        {unknownKeys.map((key) => '<< ' + (unknown[key] ?? key) + ' >>').join(', ')}
        <div className='text-xs'>{this.translate('Check the spelling, or ask for the value to be added.')}</div>
      </div> : null}

      {missing.length == 0 && unknownKeys.length == 0 ? <div className='badge badge-success'>
        {this.translate('The template is complete.')}
      </div> : null}

      <div>
        <div className='text-xs text-gray-500 mb-1'>{this.translate('Values used by this template')}:</div>
        {found.length == 0
          ? <span className='text-gray-400'>{this.translate('None')}</span>
          : <div className='flex flex-wrap gap-1'>
              {found.map((key: string, i: number) => <span key={i} className='badge badge-secondary'>{'<< ' + key + ' >>'}</span>)}
            </div>}
      </div>

      {this.state.available ? <div>
        <div className='text-xs text-gray-500 mb-1'>{this.translate('All values available to a template')}:</div>
        <div className='flex flex-wrap gap-1'>
          {this.state.available.map((item: any, i: number) => <span
            key={i}
            className={'badge ' + (item.required ? 'badge-info' : 'badge-secondary')}
            title={item.label}
          >{'<< ' + item.key + ' >>'}</span>)}
        </div>
      </div> : null}
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
              {this.inputWrapper('interval')}
              {this.inputWrapper('description')}
            </div>
          </div>
          <div className='flex-1 card'>
            <div className='card-header flex justify-between items-center'>
              <span>{this.translate('Certificate template')}</span>
              {R.id > 0 && R.template ? <button
                className='btn btn-transparent btn-small'
                disabled={this.state.isCheckingTemplate}
                onClick={() => this.checkTemplate()}
              >
                <span className='icon'><i className='fas fa-magnifying-glass'></i></span>
                <span className='text'>{this.translate('Check template')}</span>
              </button> : null}
            </div>
            <div className='card-body'>
              {this.inputWrapper('template')}
              {this.divider(this.translate('Template check'))}
              {this.renderTemplateCheck()}
            </div>
          </div>
        </div>;

      case 'schedules':
        return <TableSchedules
          uid={this.props.uid + '_table_schedules'}
          parentForm={this}
          idTraining={R.id}
          customEndpointParams={{ idTraining: R.id }}
        />;

      default:
        return super.renderTab(tabUid);
    }
  }
}
