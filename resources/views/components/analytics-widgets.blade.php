{{-- Analytics Widgets Component --}}
{{-- Usage: @include('components.analytics-widgets', ['stats' => $stats]) --}}

<div class="space-y-6">
    <!-- Interactive Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Candidate Status Distribution (Doughnut Chart) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Status Distribution</h3>
                <div class="flex space-x-2">
                    <button onclick="toggleChartType('statusChart', 'doughnut')"
                            class="px-2 py-1 text-xs bg-gray-100 rounded hover:bg-gray-200">Doughnut</button>
                    <button onclick="toggleChartType('statusChart', 'bar')"
                            class="px-2 py-1 text-xs bg-gray-100 rounded hover:bg-gray-200">Bar</button>
                </div>
            </div>
            <div class="h-64">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>

        <!-- Monthly Trend (Line Chart) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Monthly Trend</h3>
                <select id="trendPeriod" onchange="updateTrendChart()" class="text-sm border rounded px-2 py-1">
                    <option value="6">Last 6 Months</option>
                    <option value="12" selected>Last 12 Months</option>
                    <option value="24">Last 24 Months</option>
                </select>
            </div>
            <div class="h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Second Row - More Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Campus Performance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Campus Performance</h3>
            <div class="h-56">
                <canvas id="campusPerformanceChart"></canvas>
            </div>
        </div>

        <!-- Trade Distribution -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Trade Distribution</h3>
            <div class="h-56">
                <canvas id="tradeDistributionChart"></canvas>
            </div>
        </div>

        <!-- Weekly Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Weekly Activity</h3>
            <div class="h-56">
                <canvas id="weeklyActivityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Live Stats Cards with Animation -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" x-data="liveStats()">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-xs font-medium uppercase">Today's Registrations</p>
                    <p class="text-2xl font-bold mt-1" x-text="todayRegistrations">{{ $stats['today_registrations'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
            </div>
            <div class="mt-2 text-xs text-blue-100">
                <span x-show="registrationTrend > 0" class="text-green-200"><i class="fas fa-arrow-up"></i> <span x-text="registrationTrend"></span>%</span>
                <span x-show="registrationTrend < 0" class="text-red-200"><i class="fas fa-arrow-down"></i> <span x-text="Math.abs(registrationTrend)"></span>%</span>
                vs yesterday
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-xs font-medium uppercase">Departures This Week</p>
                    <p class="text-2xl font-bold mt-1">{{ $stats['week_departures'] ?? 0 }}</p>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-plane-departure text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-xs font-medium uppercase">Pending Visas</p>
                    <p class="text-2xl font-bold mt-1">{{ $stats['pending_visas'] ?? 0 }}</p>
                </div>
                <div class="bg-yellow-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-passport text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-xs font-medium uppercase">Active Training</p>
                    <p class="text-2xl font-bold mt-1">{{ $stats['in_training'] ?? 0 }}</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-graduation-cap text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-xs font-medium uppercase">Open Complaints</p>
                    <p class="text-2xl font-bold mt-1">{{ $stats['pending_complaints'] ?? 0 }}</p>
                </div>
                <div class="bg-red-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-xs font-medium uppercase">Completion Rate</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($stats['completion_rate'] ?? 0, 1) }}%</p>
                </div>
                <div class="bg-indigo-400 bg-opacity-30 rounded-full p-2">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Performance Metrics</h3>
            <div class="flex space-x-2">
                <button onclick="refreshMetrics()" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-sync-alt mr-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Metric</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Current</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Last Month</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Change</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">New Registrations</td>
                        <td class="px-4 py-3 text-right">{{ $stats['this_month_registrations'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $stats['last_month_registrations'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $regChange = ($stats['last_month_registrations'] ?? 0) > 0
                                    ? round((($stats['this_month_registrations'] ?? 0) - ($stats['last_month_registrations'] ?? 0)) / ($stats['last_month_registrations'] ?? 1) * 100, 1)
                                    : 0;
                            @endphp
                            <span class="{{ $regChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $regChange >= 0 ? '+' : '' }}{{ $regChange }}%
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(100, max(0, 50 + $regChange)) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">Departures</td>
                        <td class="px-4 py-3 text-right">{{ $stats['this_month_departures'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $stats['last_month_departures'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $depChange = ($stats['last_month_departures'] ?? 0) > 0
                                    ? round((($stats['this_month_departures'] ?? 0) - ($stats['last_month_departures'] ?? 0)) / ($stats['last_month_departures'] ?? 1) * 100, 1)
                                    : 0;
                            @endphp
                            <span class="{{ $depChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $depChange >= 0 ? '+' : '' }}{{ $depChange }}%
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(100, max(0, 50 + $depChange)) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">Avg Processing Time (Days)</td>
                        <td class="px-4 py-3 text-right">{{ $stats['avg_processing_days'] ?? 45 }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $stats['last_month_avg_processing'] ?? 48 }}</td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $procChange = ($stats['avg_processing_days'] ?? 45) - ($stats['last_month_avg_processing'] ?? 48);
                            @endphp
                            <span class="{{ $procChange <= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $procChange <= 0 ? '' : '+' }}{{ $procChange }} days
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min(100, max(0, 50 - $procChange * 2)) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">Complaint Resolution Rate</td>
                        <td class="px-4 py-3 text-right">{{ $stats['complaint_resolution_rate'] ?? 85 }}%</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $stats['last_month_resolution_rate'] ?? 82 }}%</td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $resChange = ($stats['complaint_resolution_rate'] ?? 85) - ($stats['last_month_resolution_rate'] ?? 82);
                            @endphp
                            <span class="{{ $resChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $resChange >= 0 ? '+' : '' }}{{ $resChange }}%
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $stats['complaint_resolution_rate'] ?? 85 }}%"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Chart instances
let statusChart, trendChart, campusChart, tradeChart, activityChart;

// Initialize charts on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusDistributionChart')?.getContext('2d');
    if (statusCtx) {
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Listed', 'Screening', 'Registered', 'Training', 'Visa Process', 'Departed'],
                datasets: [{
                    data: [
                        {{ $stats['listed'] ?? 0 }},
                        {{ $stats['screening'] ?? 0 }},
                        {{ $stats['registered'] ?? 0 }},
                        {{ $stats['in_training'] ?? 0 }},
                        {{ $stats['visa_processing'] ?? 0 }},
                        {{ $stats['departed'] ?? 0 }}
                    ],
                    backgroundColor: [
                        '#3B82F6', '#F59E0B', '#10B981', '#8B5CF6', '#6366F1', '#059669'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Monthly Trend Chart
    const trendCtx = document.getElementById('monthlyTrendChart')?.getContext('2d');
    if (trendCtx) {
        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($stats['trend_labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
                datasets: [{
                    label: 'Registrations',
                    data: {!! json_encode($stats['trend_registrations'] ?? [12, 19, 25, 32, 28, 35, 42, 38, 45, 52, 48, 55]) !!},
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Departures',
                    data: {!! json_encode($stats['trend_departures'] ?? [5, 8, 12, 15, 18, 22, 25, 28, 32, 35, 38, 42]) !!},
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    // Campus Performance Chart
    const campusCtx = document.getElementById('campusPerformanceChart')?.getContext('2d');
    if (campusCtx) {
        campusChart = new Chart(campusCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($stats['campus_names'] ?? ['Campus A', 'Campus B', 'Campus C', 'Campus D']) !!},
                datasets: [{
                    label: 'Candidates',
                    data: {!! json_encode($stats['campus_candidates'] ?? [45, 32, 28, 21]) !!},
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Trade Distribution Chart
    const tradeCtx = document.getElementById('tradeDistributionChart')?.getContext('2d');
    if (tradeCtx) {
        tradeChart = new Chart(tradeCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($stats['trade_names'] ?? ['Electrician', 'Plumber', 'Welder', 'Mason', 'Other']) !!},
                datasets: [{
                    data: {!! json_encode($stats['trade_counts'] ?? [35, 25, 20, 15, 5]) !!},
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#6B7280']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { padding: 10, usePointStyle: true, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // Weekly Activity Chart
    const activityCtx = document.getElementById('weeklyActivityChart')?.getContext('2d');
    if (activityCtx) {
        activityChart = new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Activities',
                    data: {!! json_encode($stats['weekly_activity'] ?? [12, 19, 15, 22, 18, 8, 5]) !!},
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

function toggleChartType(chartId, type) {
    if (chartId === 'statusChart' && statusChart) {
        statusChart.config.type = type;
        statusChart.update();
    }
}

function updateTrendChart() {
    const period = document.getElementById('trendPeriod').value;
    // In a real app, this would fetch data from the server
    console.log('Updating trend chart for period:', period);
}

function refreshMetrics() {
    // In a real app, this would fetch fresh data from the server
    window.location.reload();
}

// Live Stats Alpine Component
function liveStats() {
    return {
        todayRegistrations: {{ $stats['today_registrations'] ?? 0 }},
        registrationTrend: {{ $stats['registration_trend'] ?? 5 }},

        init() {
            // Poll for updates every 30 seconds
            setInterval(() => this.fetchUpdates(), 30000);
        },

        async fetchUpdates() {
            try {
                const response = await axios.get('/api/v1/dashboard-stats');
                if (response.data) {
                    this.todayRegistrations = response.data.today_registrations || this.todayRegistrations;
                    this.registrationTrend = response.data.registration_trend || this.registrationTrend;
                }
            } catch (e) {
                console.log('Stats update failed:', e);
            }
        }
    }
}
</script>
@endpush
