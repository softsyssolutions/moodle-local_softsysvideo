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
 *
 * @module     local_softsysvideo/analytics
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/chartjs-lazy'], function(Chart) {
    return {
        init: function(apiUrl, pluginKey) {
            fetch(apiUrl + '/api/moodle/analytics?range=30d', {
                headers: {'Authorization': 'Bearer ' + pluginKey}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var spinner = document.getElementById('ssv-analytics-spinner');
                if (spinner) { spinner.classList.add('d-none'); }

                var ctx = document.getElementById('ssv-chart-meetings');
                if (!ctx || !data.chart_data) { return; }

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.chart_data.map(function(d) { return d.date; }),
                        datasets: [{
                            label: 'Sessions',
                            data: data.chart_data.map(function(d) { return d.sessions; }),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });

                var ctx2 = document.getElementById('ssv-chart-minutes');
                if (ctx2) {
                    new Chart(ctx2, {
                        type: 'line',
                        data: {
                            labels: data.chart_data.map(function(d) { return d.date; }),
                            datasets: [{
                                label: 'Minutes',
                                data: data.chart_data.map(function(d) { return d.minutes; }),
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                }
            })
            .catch(function() {
                var spinner = document.getElementById('ssv-analytics-spinner');
                if (spinner) { spinner.classList.add('d-none'); }
                var err = document.getElementById('ssv-analytics-error');
                if (err) { err.classList.remove('d-none'); }
            });
        }
    };
});
