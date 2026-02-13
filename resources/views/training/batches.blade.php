@extends('layouts.app')
@section('title', 'Training Batches')
@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Training Batches</h2>
        <p class="text-gray-500 text-sm mt-1">Overview of all training batches</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Batch Number</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Trade</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Campus</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Candidates</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($batches as $batch)
                    @php
                        $twColors = [
                            \App\Models\Batch::STATUS_PLANNED => 'bg-yellow-100 text-yellow-800',
                            \App\Models\Batch::STATUS_ACTIVE => 'bg-green-100 text-green-800',
                            \App\Models\Batch::STATUS_COMPLETED => 'bg-gray-100 text-gray-600',
                            \App\Models\Batch::STATUS_CANCELLED => 'bg-red-100 text-red-800',
                        ];
                        $batchStatuses = \App\Models\Batch::getStatuses();
                        $twColor = $twColors[$batch->status] ?? 'bg-gray-100 text-gray-600';
                        $statusLabel = $batchStatuses[$batch->status] ?? ucfirst($batch->status);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $batch->batch_number }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $batch->trade?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $batch->campus?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $twColor }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-800 font-semibold">{{ $batch->candidates_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('reports.batch-summary', $batch) }}" class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 px-3 py-1 rounded text-xs">
                                <i class="fas fa-chart-bar mr-1"></i>Report
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
