// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Analytics AMD module for local_softsysvideo.
 * Renders daywise sessions and minutes charts using Chart.js (Moodle's chartjs-lazy).
 * Supports range selector (7d / 30d / 90d) and KPI summary cards.
 *
 * @module     local_softsysvideo/analytics
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/chartjs-lazy'], function(Ajax, Chart) {

    var meetingsChart = null;
    var minutesChart = null;
    var currentRange = '30d';

    /**
     * Set text content of an element by id.
     * @param {string} id
     * @param {string|number} value
     */
    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = (value !== undefined && value !== null) ? String(value) : '\u2014';
        }
    }

    /**
     * Fetch analytics for the given range, populate KPI cards and redraw charts.
     * @param {string} range  '7d' | '30d' | '90d'
     */
    function loadAnalytics(range) {
        var spinner = document.getElementById('ssv-analytics-spinner');
        var chartsRow = document.getElementById('ssv-charts-row');
        if (spinner) { spinner.classList.remove('d-none'); }
        if (chartsRow) { chartsRow.classList.add('d-none'); }

        Ajax.call([{
            methodname: 'local_softsysvideo_get_analytics',
            args: {range: range}
        }])[0].then(function(data) {
            if (spinner) { spinner.classList.add('d-none'); }
            if (chartsRow) { chartsRow.classList.remove('d-none'); }

            // Populate KPI summary cards.
            if (data.summary) {
                setText('ssv-analytics-total-sessions', data.summary.total_sessions);
                setText('ssv-analytics-total-minutes', data.summary.total_minutes + ' min');
                setText('ssv-analytics-total-rec-minutes', data.summary.total_recording_minutes + ' min');
                setText('ssv-analytics-recordings-count', data.summary.recordings_count);
            }

            if (!data.chart_data || !data.chart_data.length) { return; }

            var labels = data.chart_data.map(function(d) { return d.date; });
            var sessionData = data.chart_data.map(function(d) { return d.sessions; });
            var minutesData = data.chart_data.map(function(d) { return d.minutes; });

            var ctx1 = document.getElementById('ssv-chart-meetings');
            if (ctx1) {
                if (meetingsChart) { meetingsChart.destroy(); meetingsChart = null; }
                meetingsChart = new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Sessions',
                            data: sessionData,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {legend: {display: false}},
                        scales: {
                            y: {beginAtZero: true, ticks: {precision: 0}}
                        }
                    }
                });
            }

            var ctx2 = document.getElementById('ssv-chart-minutes');
            if (ctx2) {
                if (minutesChart) { minutesChart.destroy(); minutesChart = null; }
                minutesChart = new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Minutes',
                            data: minutesData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {legend: {display: false}},
                        scales: {y: {beginAtZero: true}}
                    }
                });
            }
            return;
        }).catch(function() {
            if (spinner) { spinner.classList.add('d-none'); }
            var err = document.getElementById('ssv-analytics-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    return {
        init: function() {
            // Wire up range selector buttons.
            var rangeButtons = document.querySelectorAll('.ssv-range-btn');
            rangeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    rangeButtons.forEach(function(b) {
                        b.classList.remove('btn-primary');
                        b.classList.add('btn-outline-secondary');
                    });
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-primary');
                    currentRange = btn.getAttribute('data-range') || '30d';
                    loadAnalytics(currentRange);
                });
            });

            loadAnalytics(currentRange);
        }
    };
});
