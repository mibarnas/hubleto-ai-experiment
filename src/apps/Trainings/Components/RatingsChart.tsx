import React, { Component } from 'react'
import { Bar } from 'react-chartjs-2';
import {
  Chart as ChartJS, Tooltip, Legend, BarController, BarElement, CategoryScale, LinearScale,
} from 'chart.js';

ChartJS.register(Tooltip, Legend, BarController, BarElement, CategoryScale, LinearScale);

export interface RatingsChartProps {
  labels: Array<string>,
  values: Array<number>,
  colors?: Array<string>,
}

/**
 * Renders the per-question averages.
 *
 * react-ui's HubletoChart cannot be used here: its constructor reads
 * `this.props.data`, but the class declares a bare `props;` field, which under
 * ES2022 class-field semantics is defined as undefined right after
 * super(props) -- so `this.props` is undefined and it throws. Table and Form
 * work around it by re-assigning `this.props = props`; Chart does not.
 */
export default class RatingsChart extends Component<RatingsChartProps> {
  render() {
    const labels = this.props.labels ?? [];
    const values = this.props.values ?? [];
    if (labels.length == 0) return <></>;

    return <Bar
      height={220}
      options={{
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          // Question wording is long; the table underneath carries the labels.
          x: { ticks: { display: false } },
          y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } },
        },
      }}
      data={{
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: this.props.colors ?? 'rgb(59, 130, 246)',
        }],
      }}
    />;
  }
}
