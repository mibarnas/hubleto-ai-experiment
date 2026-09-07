import React from 'react'
import { Input, InputProps, InputState } from '@hubleto/react-ui/core/Input';

/**
 * A usable replacement for the stock datetime input.
 *
 * The built-in one is a calendar-only Flatpickr: the field cannot be typed into
 * (`allowInput: false`), so setting a time means clicking through a picker, and
 * the value it produces is formatted as `YYYY-MM-DD H:mm:s` -- hours and
 * seconds without a leading zero, which is not a valid MySQL DATETIME literal.
 *
 * This one is a native `datetime-local` field: it can be typed or pasted into,
 * it opens the browser's own picker, and it always writes back a zero-padded
 * `YYYY-MM-DD HH:mm:ss`. "Now" and "Clear" cover the two things people actually
 * do with these fields.
 */

/** `2026-09-07 14:05:00` -> `2026-09-07T14:05` (what the native input wants). */
export function toInputValue(value: any): string {
  if (!value) return '';

  const text = String(value).trim().replace(' ', 'T');
  const match = text.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{1,2}):(\d{2})/);
  if (!match) return '';

  const [, year, month, day, hour, minute] = match;
  return `${year}-${month}-${day}T${hour.padStart(2, '0')}:${minute}`;
}

/** `2026-09-07T14:05` -> `2026-09-07 14:05:00`. */
export function toStoredValue(value: string): string {
  if (!value) return '';

  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
  if (!match) return '';

  const [, year, month, day, hour, minute, second] = match;
  return `${year}-${month}-${day} ${hour.padStart(2, '0')}:${minute}:${(second ?? '00').padStart(2, '0')}`;
}

export function toReadableValue(value: any): string {
  const inputValue = toInputValue(value);
  if (!inputValue) return '';

  const [date, time] = inputValue.split('T');
  const [year, month, day] = date.split('-');
  return `${day}.${month}.${year} ${time}`;
}

function nowStored(): string {
  const now = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
    + ` ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

export default class InputTimestamp extends Input<InputProps, InputState> {
  static defaultProps = {
    ...Input.defaultProps,
    inputClassName: 'timestamp',
  };

  translationContext: string = 'Hubleto\\App\\Custom\\Trainings\\Loader';
  translationContextInner: string = 'Components\\InputTimestamp';

  constructor(props: InputProps) {
    super(props);
    this.state = this.getStateFromProps(props);
  }

  renderValueElement() {
    const readable = toReadableValue(this.state.value);
    if (!readable) return <span className='no-value'></span>;

    return <span className='flex gap-2 items-center'>
      <i className='fas fa-clock text-gray-400'></i>
      {readable}
    </span>;
  }

  renderInputElement() {
    return <div className='flex gap-1 items-center'>
      <input
        ref={this.refInput}
        type='datetime-local'
        step={60}
        className={
          (this.state.invalid ? 'is-invalid' : '')
          + ' ' + (this.state.cssClass ?? '')
          + ' ' + (this.state.readonly ? 'bg-muted' : '')
        }
        value={toInputValue(this.state.value)}
        readOnly={this.state.readonly}
        disabled={this.state.readonly}
        onChange={(e) => this.onChange(toStoredValue(e.target.value))}
      />
      {this.state.readonly ? null : <>
        <button
          type='button'
          className='btn btn-transparent btn-small'
          title={this.translate('Set to now')}
          onClick={() => this.onChange(nowStored())}
        >
          <span className='icon'><i className='fas fa-clock'></i></span>
        </button>
        <button
          type='button'
          className='btn btn-transparent btn-small'
          title={this.translate('Clear')}
          onClick={() => this.onChange('')}
        >
          <span className='icon'><i className='fas fa-times'></i></span>
        </button>
      </>}
    </div>;
  }
}
