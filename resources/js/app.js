import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import './gab';