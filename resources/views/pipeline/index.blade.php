@extends('layouts.app')

@section('title', 'Pipeline Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Pipeline Dashboard</h1>
                <p class="text-gray-600 mt-1">Overview of all candidates by lifecycle stage</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('pipeline.export') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-download mr-2"></i>Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Bottlenecks Alert -->
    @if(count($bottlenecks) > 0)
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Pipeline Bottlenecks Detected</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($bottlenecks as $bottleneck)
                        <li class="{{ $bottleneck['severity'] === 'high' ? 'font-semibold' : '' }}">
                            {{ $bottleneck['message'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Pipeline Funnel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Candidate Pipeline</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
            @foreach($stages as $index => $stage)
                <div class="relative">
                    <!-- Stage Card -->
                    <div class="bg-{{ $stage['color'] }}-50 border-2 border-{{ $stage['color'] }}-200 rounded-xl p-4 text-center 
                        hover:shadow-lg transition cursor-pointer"
                        onclick="toggleBreakdown({{ $index }})">
                        <div class="w-12 h-12 bg-{{ $stage['color'] }}-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="{{ $stage['icon'] }} text-{{ $stage['color'] }}-600 text-xl"></i>
                        </div>
                        <p class="text-3xl font-bold text-{{ $stage['color'] }}-700">{{ number_format($stage['count']) }}</p>
                        <p class="text-xs text-{{ $stage['color'] }}-600 font-medium mt-1">{{ $stage['name'] }}</p>
                    </div>
                    
                    <!-- Arrow between stages -->
                    @if($index < count($stages) - 1)
                        <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2">
                            <i class="fas fa-chevron-right text-gray-300"></i>
                        </div>
                    @endif

                    <!-- Breakdown dropdown -->
                    <div id="breakdown-{{ $index }}" class="hidden mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 absolute z-10 w-48">
                        @foreach($stage['breakdown'] as $status => $data)
                            <a href="{{ route('pipeline.by-status', $status) }}" 
                               class="flex justify-between items-center py-1.5 px-2 hover:bg-gray-50 rounded">
                                <span class="text-sm text-gray-700">{{ $data['label'] }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $data['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Terminal Status Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Deferred -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Deferred</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ number_format($statusCounts['deferred'] ?? 0) }}</p>
                </div>
                <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-pause-circle text-yellow-600 text-2xl"></i>
                </div>
            </div>
            <a href="{{ route('pipeline.by-status', 'deferred') }}" class="text-yellow-600 hover:text-yellow-800 text-sm mt-3 inline-block">
                View all →
            </a>
        </div>

        <!-- Rejected -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Rejected</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($statusCounts['rejected'] ?? 0) }}</p>
                </div>
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                </div>
            </div>
            <a href="{{ route('pipeline.by-status', 'rejected') }}" class="text-red-600 hover:text-red-800 text-sm mt-3 inline-block">
                View all →
            </a>
        </div>

        <!-- Withdrawn -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Withdrawn</p>
                    <p class="text-3xl font-bold text-gray-600">{{ number_format($statusCounts['withdrawn'] ?? 0) }}</p>
                </div>
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-slash text-gray-600 text-2xl"></i>
                </div>
            </div>
            <a href="{{ route('pipeline.by-status', 'withdrawn') }}" class="text-gray-600 hover:text-gray-800 text-sm mt-3 inline-block">
                View all →
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Requires Attention -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Requires Attention</h2>
                <p class="text-sm text-gray-500 mt-1">Candidates that are overdue for progression</p>
            </div>
            
            <div class="overflow-x-auto">
                @if($requiresAttention->count() > 0)
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($requiresAttention as $candidate)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('candidates.show', $candidate) }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $candidate->name }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $candidate->btevta_id ?? 'No ID' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $candidate->status === 'screening' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                            {{ $candidate->status === 'visa_process' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $candidate->status === 'departed' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ \App\Enums\CandidateStatus::tryFrom($candidate->status)?->label() ?? $candidate->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $candidate->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-check-circle text-green-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">All candidates are on track!</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Transitions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Recent Transitions</h2>
                <p class="text-sm text-gray-500 mt-1">Latest status changes in the system</p>
            </div>
            
            <div class="overflow-y-auto max-h-96">
                @if($recentTransitions->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($recentTransitions as $activity)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            by {{ $activity->causer?->name ?? 'System' }}
                                        </p>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-history text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">No recent transitions</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function toggleBreakdown(index) {
    // Close all other breakdowns
    document.querySelectorAll('[id^="breakdown-"]').forEach(el => {
        if (el.id !== 'breakdown-' + index) {
            el.classList.add('hidden');
        }
    });
    
    // Toggle the clicked breakdown
    const breakdown = document.getElementById('breakdown-' + index);
    breakdown.classList.toggle('hidden');
}

// Close breakdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[id^="breakdown-"]') && !event.target.closest('[onclick^="toggleBreakdown"]')) {
        document.querySelectorAll('[id^="breakdown-"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});
</script>
@endsection
