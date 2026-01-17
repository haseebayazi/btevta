@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Training Management</h2>
        <a href="{{ route('training.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Training
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Active Batches</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['active_batches'] ?? 0 }}</p>
                </div>
                <i class="fas fa-layer-group text-blue-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">In Training</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>
                <i class="fas fa-graduation-cap text-yellow-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Completed</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['completed'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-green-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificates</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $stats['completed_count'] ?? 0 }}</p>
                </div>
                <i class="fas fa-certificate text-purple-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">At Risk</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['at_risk'] ?? 0 }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-red-400 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions & At-Risk Candidates -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('training.index') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-list text-blue-600 mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900">View All Training</p>
                        <p class="text-sm text-gray-600">Manage candidates in training</p>
                    </div>
                </a>
                <a href="{{ route('reports.assessment-analytics') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <i class="fas fa-chart-bar text-purple-600 mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900">Assessment Analytics</p>
                        <p class="text-sm text-gray-600">View detailed analytics</p>
                    </div>
                </a>
                <a href="{{ route('reports.trainer-performance') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <i class="fas fa-user-tie text-green-600 mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900">Trainer Performance</p>
                        <p class="text-sm text-gray-600">Monitor trainer metrics</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- At-Risk Candidates -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">At-Risk Candidates</h3>
                <span class="badge badge-danger">{{ $stats['at_risk'] ?? 0 }} candidates</span>
            </div>
            @if(isset($atRiskCandidates) && $atRiskCandidates->count() > 0)
                <div class="space-y-2">
                    @foreach($atRiskCandidates->take(5) as $candidate)
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $candidate->name }}</p>
                                <p class="text-sm text-gray-600">{{ $candidate->btevta_id }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
                                <p class="text-sm text-red-600 mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $candidate->at_risk_reason ?? 'Low attendance or performance' }}
                                </p>
                            </div>
                            <a href="{{ route('training.show', $candidate) }}" class="btn btn-sm btn-outline-primary ml-3">
                                View
                            </a>
                        </div>
                    @endforeach
                </div>
                @if($atRiskCandidates->count() > 5)
                    <div class="text-center mt-4">
                        <a href="{{ route('training.index', ['filter' => 'at_risk']) }}" class="text-blue-600 hover:text-blue-800">
                            View all {{ $atRiskCandidates->count() }} at-risk candidates →
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                    <p>No at-risk candidates</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Active Batches Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Active Training Batches</h3>

        <div class="overflow-x-auto">
            @if($activeBatches->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Batch Code</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Candidates</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeBatches as $batch)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold">{{ $batch->batch_code ?? 'N/A' }}</td>
                                <td class="px-6 py-4 font-medium">{{ $batch->name }}</td>
                                <td class="px-6 py-4">{{ $batch->campus->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $batch->trade->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold">{{ $batch->candidates_count ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                        {{ ucfirst($batch->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('batches.show', $batch->id) }}"
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $activeBatches->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No active training batches</p>
            @endif
        </div>
    </div>
</div>
@endsection