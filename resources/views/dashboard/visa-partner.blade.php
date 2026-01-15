@extends('layouts.app')

@section('title', 'Visa Partner Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Visa Partner Banner -->
    <div class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Visa Partner Dashboard</h1>
                <p class="text-amber-100 mt-1">Visa Processing Portal</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-amber-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-amber-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-600">Visa Partner Representative</p>
    </div>

    <!-- Visa Pipeline Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-gray-600 text-xs font-medium uppercase">Interview</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($roleData['pending_interview'] ?? 0) }}</p>
            <p class="text-xs text-blue-600">Pending</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-indigo-500">
            <p class="text-gray-600 text-xs font-medium uppercase">Trade Test</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($roleData['pending_trade_test'] ?? 0) }}</p>
            <p class="text-xs text-indigo-600">Pending</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
            <p class="text-gray-600 text-xs font-medium uppercase">Medical</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($roleData['pending_medical'] ?? 0) }}</p>
            <p class="text-xs text-purple-600">Pending</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-pink-500">
            <p class="text-gray-600 text-xs font-medium uppercase">Biometric</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($roleData['pending_biometric'] ?? 0) }}</p>
            <p class="text-xs text-pink-600">Pending</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
            <p class="text-gray-600 text-xs font-medium uppercase">Visa Issue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($roleData['pending_visa_issue'] ?? 0) }}</p>
            <p class="text-xs text-green-600">Pending</p>
        </div>
    </div>

    <!-- Visa Processing Pipeline Visual -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Visa Processing Pipeline</h3>
        <div class="flex items-center justify-between">
            @php
                $stages = [
                    ['name' => 'Interview', 'count' => $roleData['pending_interview'] ?? 0, 'color' => 'blue'],
                    ['name' => 'Trade Test', 'count' => $roleData['pending_trade_test'] ?? 0, 'color' => 'indigo'],
                    ['name' => 'Medical', 'count' => $roleData['pending_medical'] ?? 0, 'color' => 'purple'],
                    ['name' => 'Biometric', 'count' => $roleData['pending_biometric'] ?? 0, 'color' => 'pink'],
                    ['name' => 'Visa Issue', 'count' => $roleData['pending_visa_issue'] ?? 0, 'color' => 'green'],
                ];
                $total = array_sum(array_column($stages, 'count')) ?: 1;
            @endphp
            @foreach($stages as $index => $stage)
            <div class="flex-1 text-center relative">
                <div class="w-12 h-12 mx-auto bg-{{ $stage['color'] }}-100 rounded-full flex items-center justify-center mb-2">
                    <span class="text-{{ $stage['color'] }}-700 font-bold">{{ $stage['count'] }}</span>
                </div>
                <p class="text-xs text-gray-600">{{ $stage['name'] }}</p>
                @if($index < count($stages) - 1)
                <div class="absolute top-6 left-1/2 w-full h-0.5 bg-gray-200"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Visas Issued -->
    @if(!empty($roleData['recent_visas']) && count($roleData['recent_visas']) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recently Issued Visas</h3>
            <a href="{{ route('visa-processing.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Candidate</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">TheLeap ID</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Visa Number</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Issue Date</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($roleData['recent_visas'] as $visa)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $visa->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $visa->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $visa->visa_number ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $visa->visa_issue_date ? \Carbon\Carbon::parse($visa->visa_issue_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-success">Issued</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('visa-processing.index') }}" class="bg-white hover:bg-amber-50 border-2 border-amber-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-passport text-amber-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">All Applications</h4>
            <p class="text-sm text-gray-600 mt-1">View visa applications</p>
        </a>
        <a href="{{ route('visa-processing.index', ['stage' => 'interview']) }}" class="bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-user-tie text-blue-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Interviews</h4>
            <p class="text-sm text-gray-600 mt-1">Schedule & manage</p>
        </a>
        <a href="{{ route('visa-processing.index', ['stage' => 'medical']) }}" class="bg-white hover:bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-file-medical text-purple-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Medicals</h4>
            <p class="text-sm text-gray-600 mt-1">Medical clearances</p>
        </a>
        <a href="{{ route('reports.visa-timeline') }}" class="bg-white hover:bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-chart-line text-green-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Reports</h4>
            <p class="text-sm text-gray-600 mt-1">Visa timeline reports</p>
        </a>
    </div>

</div>
@endsection
