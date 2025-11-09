@extends('layouts.app')
@section('title', 'Departure Compliance Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Departure Compliance Report</h1>
        <div class="flex gap-2">
            <button onclick="exportPDF()" class="btn btn-danger">
                <i class="fas fa-file-pdf mr-2"></i>Export PDF
            </button>
            <button onclick="exportExcel()" class="btn btn-success">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </button>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Date Range</label>
                <select name="range" class="form-input">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div>
                <label class="form-label">OEP</label>
                <select name="oep_id" class="form-input">
                    <option value="">All OEPs</option>
                    @foreach($oeps as $oep)
                        <option value="{{ $oep->id }}">{{ $oep->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="compliant">Compliant</option>
                    <option value="at_risk">At Risk</option>
                    <option value="non_compliant">Non-Compliant</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Generate Report</button>
            </div>
        </form>
    </div>

    <!-- Compliance Overview -->
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Compliant</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['compliant'] }}</p>
            <p class="text-sm text-green-700 mt-1">{{ $stats['compliant_percentage'] }}%</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">At Risk</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['at_risk'] }}</p>
            <p class="text-sm text-yellow-700 mt-1">{{ $stats['at_risk_percentage'] }}%</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Non-Compliant</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['non_compliant'] }}</p>
            <p class="text-sm text-red-700 mt-1">{{ $stats['non_compliant_percentage'] }}%</p>
        </div>
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Departures</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Avg Completion</p>
            <p class="text-3xl font-bold text-purple-900">{{ $stats['avg_days'] }}d</p>
        </div>
    </div>

    <!-- Compliance by OEP -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Compliance by OEP</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">OEP</th>
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">Compliant</th>
                        <th class="px-4 py-3 text-center">At Risk</th>
                        <th class="px-4 py-3 text-center">Non-Compliant</th>
                        <th class="px-4 py-3 text-center">Compliance Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($oepStats as $oep)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $oep->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $oep->total }}</td>
                        <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $oep->compliant }}</td>
                        <td class="px-4 py-3 text-center text-yellow-600 font-semibold">{{ $oep->at_risk }}</td>
                        <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ $oep->non_compliant }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 rounded-full h-2" style="width: {{ $oep->compliance_rate }}%"></div>
                                </div>
                                <span class="badge badge-{{ $oep->compliance_rate >= 80 ? 'success' : 'warning' }}">
                                    {{ number_format($oep->compliance_rate, 1) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Compliance Trend</h3>
            <canvas id="complianceTrendChart" height="300"></canvas>
        </div>
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Stage Completion Distribution</h3>
            <canvas id="stageDistributionChart" height="300"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const trendData = @json($trendData);
const distributionData = @json($distributionData);

new Chart(document.getElementById('complianceTrendChart'), {
    type: 'line',
    data: {
        labels: trendData.labels,
        datasets: [{
            label: 'Compliant',
            data: trendData.compliant,
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)'
        }, {
            label: 'Non-Compliant',
            data: trendData.non_compliant,
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)'
        }]
    }
});

new Chart(document.getElementById('stageDistributionChart'), {
    type: 'doughnut',
    data: {
        labels: distributionData.labels,
        datasets: [{
            data: distributionData.values,
            backgroundColor: [
                'rgb(34, 197, 94)',
                'rgb(251, 146, 60)',
                'rgb(239, 68, 68)',
                'rgb(59, 130, 246)'
            ]
        }]
    }
});

function exportPDF() {
    window.location.href = "{{ route('departure.compliance-report.pdf') }}";
}

function exportExcel() {
    window.location.href = "{{ route('departure.compliance-report.excel') }}";
}
</script>
@endpush
@endsection