@extends('layouts.app')

@section('title', 'Campus Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Campus Banner -->
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ $roleData['campus']->name ?? 'Campus' }} Dashboard</h1>
                <p class="text-emerald-100 mt-1">Campus Administration Portal</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-emerald-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-emerald-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-600">Campus Administrator</p>
            </div>
            <a href="{{ route('dashboard.compliance-monitoring') }}" class="btn btn-primary">
                <i class="fas fa-shield-alt mr-2"></i>Compliance Status
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

    <!-- Campus Statistics -->
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

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pending Registrations</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['pending_registrations'] ?? 0) }}</p>
                </div>
                <div class="bg-orange-100 rounded-full p-4">
                    <i class="fas fa-user-clock text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Batches</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['active_batches']) }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-chalkboard-teacher text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Batches -->
    @if(!empty($roleData['active_batches']) && count($roleData['active_batches']) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Active Batches</h3>
            <a href="{{ route('batches.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($roleData['active_batches'] as $batch)
            <div class="border rounded-lg p-4 hover:border-blue-500 transition">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900">{{ $batch->batch_code }}</h4>
                    <span class="badge badge-success">Active</span>
                </div>
                <p class="text-sm text-gray-600">{{ $batch->trade->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600">Instructor: {{ $batch->instructor->name ?? 'Not Assigned' }}</p>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="fas fa-users mr-1"></i>{{ $batch->candidates_count ?? 0 }} students
                    </span>
                    <a href="{{ route('batches.show', $batch) }}" class="text-blue-600 hover:underline">Details</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Today's Attendance Summary -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Today's Attendance Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <p class="text-sm text-green-700">Present</p>
                <p class="text-2xl font-bold text-green-800">{{ $roleData['attendance_today']['present']->count() ?? 0 }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <p class="text-sm text-red-700">Absent</p>
                <p class="text-2xl font-bold text-red-800">{{ $roleData['attendance_today']['absent']->count() ?? 0 }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <p class="text-sm text-yellow-700">Late</p>
                <p class="text-2xl font-bold text-yellow-800">{{ $roleData['attendance_today']['late']->count() ?? 0 }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <p class="text-sm text-blue-700">Excused</p>
                <p class="text-2xl font-bold text-blue-800">{{ $roleData['attendance_today']['excused']->count() ?? 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Pipeline Chart & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Candidate Distribution</h3>
            <div class="h-64">
                <canvas id="candidatePipelineChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('training.attendance.index') }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                    <div class="flex items-center">
                        <i class="fas fa-clipboard-check text-green-600 mr-3"></i>
                        <span class="text-gray-700">Mark Attendance</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <a href="{{ route('candidates.create') }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                    <div class="flex items-center">
                        <i class="fas fa-user-plus text-blue-600 mr-3"></i>
                        <span class="text-gray-700">Add Candidate</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <a href="{{ route('batches.create') }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                    <div class="flex items-center">
                        <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                        <span class="text-gray-700">Create Batch</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                    <div class="flex items-center">
                        <i class="fas fa-chart-bar text-indigo-600 mr-3"></i>
                        <span class="text-gray-700">View Reports</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Campus Activities</h3>
        </div>
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-user text-emerald-600 text-sm"></i>
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

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Candidate Pipeline Doughnut Chart
    const pipelineCtx = document.getElementById('candidatePipelineChart');
    if (pipelineCtx) {
        new Chart(pipelineCtx, {
            type: 'doughnut',
            data: {
                labels: ['Screening', 'Registered', 'In Training', 'Visa Processing', 'Departed'],
                datasets: [{
                    data: [
                        {{ $stats['screening'] ?? 0 }},
                        {{ $stats['registered'] ?? 0 }},
                        {{ $stats['in_training'] ?? 0 }},
                        {{ $stats['visa_processing'] ?? 0 }},
                        {{ $stats['departed'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(251, 191, 36, 0.8)',  // yellow
                        'rgba(59, 130, 246, 0.8)',  // blue
                        'rgba(16, 185, 129, 0.8)',  // green
                        'rgba(139, 92, 246, 0.8)',  // purple
                        'rgba(236, 72, 153, 0.8)'   // pink
                    ],
                    borderColor: [
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>
@endpush
@endsection
