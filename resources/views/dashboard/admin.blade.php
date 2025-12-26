@extends('layouts.app')

@section('title', 'Admin Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- WASL Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ config('app.full_name') }}</h1>
                <p class="text-blue-100 mt-1">{{ config('app.tagline') }}</p>
                <p class="text-blue-200 text-sm mt-1">Admin Dashboard</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-blue-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-blue-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }} Dashboard</p>
            </div>
            <a href="{{ route('dashboard.compliance-monitoring') }}" class="btn btn-primary">
                <i class="fas fa-shield-alt mr-2"></i>Compliance Monitor
            </a>
        </div>
    </div>

    <!-- Alerts Section -->
    @if(count($alerts) > 0)
    <div class="space-y-3">
        @foreach($alerts as $alert)
        <div class="bg-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-50 border border-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-{{ $alert['type'] === 'danger' ? 'exclamation-circle' : ($alert['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle') }} text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-600 mr-3"></i>
                    <span class="text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-800">{{ $alert['message'] }}</span>
                </div>
                <a href="{{ $alert['action_url'] }}" class="text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-700 hover:underline text-sm font-medium">
                    View Details <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Candidates</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_candidates']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">In Training</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['in_training']) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-graduation-cap text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Visa Processing</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['visa_processing']) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-passport text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Departed</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['departed']) }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-plane-departure text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Campus Performance Overview -->
    @if(!empty($roleData['campuses']))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Campus Performance Overview</h3>
            <a href="{{ route('reports.campus-performance') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Full Report <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Campus</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Candidates</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Performance</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($roleData['campuses']->take(5) as $campus)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $campus->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $campus->candidates_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    @php $rate = $stats['total_candidates'] > 0 ? min(100, ($campus->candidates_count / $stats['total_candidates']) * 100 * 5) : 0; @endphp
                                    <div class="bg-blue-600 rounded-full h-2" style="width: {{ $rate }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Monthly Trends Chart -->
    @if(!empty($roleData['monthly_trends']))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Monthly Trends (Last 6 Months)</h3>
        <div class="h-64">
            <canvas id="monthlyTrendsChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Process Pipeline & Quick Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Candidate Pipeline</h3>
            <div class="space-y-4">
                @foreach(['listed' => 'Listed', 'screening' => 'Screening', 'registered' => 'Registered', 'in_training' => 'In Training', 'visa_processing' => 'Visa Processing', 'departed' => 'Departed'] as $key => $label)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats[$key] ?? 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? (($stats[$key] ?? 0) / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Stats</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Active Batches</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['active_batches']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Pending Complaints</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['pending_complaints']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-yellow-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Pending Reply</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['pending_correspondence']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-gray-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Rejected</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['rejected']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Activities</h3>
            <a href="{{ route('admin.audit-logs') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">
                        <span class="font-medium">{{ $activity->user_name }}</span>
                        <span class="text-gray-600">{{ $activity->action }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                    </p>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-500 py-8">No recent activities</p>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('import.candidates.form') }}" class="bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-file-import text-blue-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Import Candidates</h4>
            <p class="text-sm text-gray-600 mt-1">Bulk import from Excel</p>
        </a>
        <a href="{{ route('candidates.create') }}" class="bg-white hover:bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-user-plus text-green-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Add Candidate</h4>
            <p class="text-sm text-gray-600 mt-1">Register new candidate</p>
        </a>
        <a href="{{ route('reports.index') }}" class="bg-white hover:bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-chart-bar text-purple-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Generate Report</h4>
            <p class="text-sm text-gray-600 mt-1">View analytics</p>
        </a>
        <a href="{{ route('dashboard.compliance-monitoring') }}" class="bg-white hover:bg-indigo-50 border-2 border-indigo-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-shield-alt text-indigo-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Compliance Monitor</h4>
            <p class="text-sm text-gray-600 mt-1">Track compliance rates</p>
        </a>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($roleData['monthly_trends']))
    // Monthly Trends Chart
    const trendsCtx = document.getElementById('monthlyTrendsChart');
    if (trendsCtx) {
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(collect($roleData['monthly_trends'])->pluck('month')) !!},
                datasets: [
                    {
                        label: 'Registered',
                        data: {!! json_encode(collect($roleData['monthly_trends'])->pluck('registered')) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Departed',
                        data: {!! json_encode(collect($roleData['monthly_trends'])->pluck('departed')) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endpush
@endsection
