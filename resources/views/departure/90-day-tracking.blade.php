@extends('layouts.app')
@section('title', '90-Day Compliance Tracking')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">90-Day Compliance Tracking</h1>
            <p class="text-gray-600 mt-1">{{ $criticalCount }} candidates within 90-day window</p>
        </div>
        <button onclick="exportReport()" class="btn btn-danger">
            <i class="fas fa-file-pdf mr-2"></i>Export Critical List
        </button>
    </div>

    <!-- Alert Summary -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-red-50 border-red-200">
            <div class="text-red-800">
                <p class="text-sm font-medium">Critical (<30 days)</p>
                <p class="text-4xl font-bold">{{ $criticalCount }}</p>
            </div>
        </div>
        <div class="card bg-orange-50 border-orange-200">
            <div class="text-orange-800">
                <p class="text-sm font-medium">Warning (30-60 days)</p>
                <p class="text-4xl font-bold">{{ $warningCount }}</p>
            </div>
        </div>
        <div class="card bg-yellow-50 border-yellow-200">
            <div class="text-yellow-800">
                <p class="text-sm font-medium">Attention (60-90 days)</p>
                <p class="text-4xl font-bold">{{ $attentionCount }}</p>
            </div>
        </div>
        <div class="card bg-green-50 border-green-200">
            <div class="text-green-800">
                <p class="text-sm font-medium">Compliant</p>
                <p class="text-4xl font-bold">{{ $compliantCount }}</p>
            </div>
        </div>
    </div>

    <!-- Critical List -->
    @if($criticalDepartures->count() > 0)
    <div class="card mb-6 border-red-300">
        <div class="bg-red-50 px-6 py-3 border-b border-red-200">
            <h2 class="text-xl font-bold text-red-900">
                <i class="fas fa-exclamation-circle mr-2"></i>Critical - Immediate Action Required
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Candidate</th>
                        <th class="px-4 py-3 text-left">Passport</th>
                        <th class="px-4 py-3 text-center">Days Remaining</th>
                        <th class="px-4 py-3 text-center">Current Stage</th>
                        <th class="px-4 py-3 text-center">OEP</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($criticalDepartures as $departure)
                    <tr class="bg-red-50">
                        <td class="px-4 py-3 font-medium">{{ $departure->candidate->name }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->passport_number }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-danger text-lg font-bold">
                                {{ $departure->days_remaining }} days
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-secondary">Stage {{ $departure->current_stage }}/6</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm">{{ $departure->oep->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('departure.show', $departure) }}" class="btn btn-sm btn-danger">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Warning & Attention Lists -->
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Warning List -->
        <div class="card border-orange-200">
            <div class="bg-orange-50 px-6 py-3 border-b border-orange-200 mb-4">
                <h3 class="text-lg font-bold text-orange-900">Warning (30-60 days)</h3>
            </div>
            <div class="space-y-2">
                @forelse($warningDepartures as $departure)
                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                    <div>
                        <p class="font-medium">{{ $departure->candidate->name }}</p>
                        <p class="text-sm text-gray-600">{{ $departure->oep->name }}</p>
                    </div>
                    <span class="badge badge-warning">{{ $departure->days_remaining }}d</span>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No cases in warning period</p>
                @endforelse
            </div>
        </div>

        <!-- Attention List -->
        <div class="card border-yellow-200">
            <div class="bg-yellow-50 px-6 py-3 border-b border-yellow-200 mb-4">
                <h3 class="text-lg font-bold text-yellow-900">Attention (60-90 days)</h3>
            </div>
            <div class="space-y-2">
                @forelse($attentionDepartures as $departure)
                <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                    <div>
                        <p class="font-medium">{{ $departure->candidate->name }}</p>
                        <p class="text-sm text-gray-600">{{ $departure->oep->name }}</p>
                    </div>
                    <span class="badge badge-secondary">{{ $departure->days_remaining }}d</span>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No cases in attention period</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportReport() {
    window.location.href = "{{ route('departure.tracking-90-days.export') }}";
}
</script>
@endpush
@endsection