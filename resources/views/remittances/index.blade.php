@extends('layouts.app')

@section('title', 'Remittance Management - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Management</h1>
            <p class="text-gray-600 mt-1">Track and manage remittance inflows from deployed workers</p>
        </div>
        <div>
            @can('create', App\Models\Remittance::class)
            <a href="{{ route('remittances.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Record Remittance
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Remittances -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Remittances</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_count']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-money-bill-transfer text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Amount</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_amount'], 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">PKR</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-coins text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Average Amount -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Average Amount</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['avg_amount'], 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">PKR</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-chart-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Proof Compliance -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Proof Compliance</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['proof_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['with_proof']) }} with proof</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-file-invoice text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('remittances.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="flagged" {{ request('status') == 'flagged' ? 'selected' : '' }}>Flagged</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Purpose</label>
                <select name="purpose" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Purposes</option>
                    @foreach(config('remittance.purposes') as $key => $label)
                    <option value="{{ $key }}" {{ request('purpose') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <select name="year" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Years</option>
                    @for($y = date('Y'); $y >= 2020; $y--)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Remittances Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Remittance Records</h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('remittances.export', 'excel') }}" class="text-green-600 hover:text-green-700 text-sm">
                    <i class="fas fa-file-excel mr-1"></i>Export Excel
                </a>
                <a href="{{ route('remittances.export', 'pdf') }}" class="text-red-600 hover:text-red-700 text-sm">
                    <i class="fas fa-file-pdf mr-1"></i>Export PDF
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receiver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proof</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($remittances as $remittance)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $remittance->transfer_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $remittance->candidate->full_name }}</div>
                            <div class="text-sm text-gray-500">{{ $remittance->candidate->cnic }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ number_format($remittance->amount, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $remittance->currency }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ config('remittance.purposes.' . $remittance->primary_purpose) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $remittance->receiver_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ config('remittance.statuses.' . $remittance->status . '.class') }}">
                                {{ config('remittance.statuses.' . $remittance->status . '.label') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($remittance->has_proof)
                            <i class="fas fa-check-circle text-green-500"></i>
                            @else
                            <i class="fas fa-times-circle text-red-500"></i>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('remittances.show', $remittance) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('update', $remittance)
                            <a href="{{ route('remittances.edit', $remittance) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                            @can('delete', $remittance)
                            <form action="{{ route('remittances.destroy', $remittance) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this remittance?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No remittances found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($remittances->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $remittances->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
