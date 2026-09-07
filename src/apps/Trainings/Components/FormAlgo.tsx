import React from 'react'
import FormExtended, { FormExtendedProps, FormExtendedState } from '@hubleto/react-ui/ext/FormExtended';

/**
 * Base form for this project's records.
 *
 * Adds one behaviour every form here needs: the tabs that list related records
 * (schedules of a training, attendees of a date, certificates of a worker, ...)
 * only appear once the record exists. Opening "add training" used to show a
 * Schedules tab that could do nothing except say "save the training first".
 *
 * Subclasses describe their tabs as a main tab plus the related ones, rather
 * than overriding `getTabsLeft()` directly:
 *
 *   getMainTab()     the always-present first tab
 *   getRelatedTabs() the tabs that need a saved record
 */
export default class FormAlgo<P, S> extends FormExtended<FormExtendedProps, FormExtendedState> {

  getMainTab(): any {
    return { uid: 'default', title: <b>{this.translate('Detail')}</b> };
  }

  getRelatedTabs(): Array<any> {
    return [];
  }

  /**
   * `state.record` is only there once the record has loaded; before that
   * `props.id` already tells us whether this is an existing record (-1 means
   * "new"), so the tabs are right on the very first render too.
   */
  isRecordCreated(): boolean {
    const id = this.state?.record?.id ?? this.props?.id ?? -1;
    return id > 0;
  }

  getTabsLeft() {
    return this.isRecordCreated()
      ? [this.getMainTab(), ...this.getRelatedTabs()]
      : [this.getMainTab()];
  }

  /**
   * `state.tabs` is computed once in the constructor, so it has to be refreshed
   * whenever the record appears -- after it loads, and right after a brand new
   * record is saved for the first time.
   */
  syncTabs() {
    const tabs = this.getTabs();
    if (tabs.length !== (this.state.tabs ?? []).length) {
      this.setState({ tabs: tabs } as any);
    }
  }

  onAfterFormInitialized() {
    super.onAfterFormInitialized();
    this.syncTabs();
  }

  onAfterSaveRecord(saveResponse: any, customSaveOptions?: any) {
    super.onAfterSaveRecord(saveResponse, customSaveOptions);
    this.syncTabs();
  }
}
