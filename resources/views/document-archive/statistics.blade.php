@extends('layouts.app')
@section('title', 'Document Statistics')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Document Archive Statistics</h1>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Documents</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Active</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['active'] }}</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Expired</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['expired'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Storage Used</p>
            <p class="text-3xl font-bold text-purple-900">{{ $stats['storage'] }}MB</p>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-xl font-bold mb-4">Documents by Category</h2>
        <canvas id="categoryChart" height="80"></canvas>
    </div>

    @if(isset($stats['by_type']) && count($stats['by_type']) > 0)
    <div class="card p-6 mt-6">
        <h2 class="text-xl font-bold mb-4">Document Type Breakdown</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['by_type'] as $type => $count)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $type)) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('categoryChart');

        @if(isset($stats['by_type']) && count($stats['by_type']) > 0)
        const data = {
            labels: [
                @foreach($stats['by_type'] as $type => $count)
                    '{{ ucwords(str_replace("_", " ", $type)) }}',
                @endforeach
            ],
            datasets: [{
                label: 'Documents by Category',
                data: [
                    @foreach($stats['by_type'] as $type => $count)
                        {{ $count }},
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(239, 68, 68, 0.5)',
                    'rgba(168, 85, 247, 0.5)',
                    'rgba(245, 158, 11, 0.5)',
                    'rgba(236, 72, 153, 0.5)',
                    'rgba(20, 184, 166, 0.5)',
                    'rgba(251, 146, 60, 0.5)',
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(168, 85, 247, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(20, 184, 166, 1)',
                    'rgba(251, 146, 60, 1)',
                ],
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
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
        };

        new Chart(ctx, config);
        @else
        // No data available
        ctx.getContext('2d').fillText('No data available', 10, 50);
        @endif
    });
</script>
@endpush