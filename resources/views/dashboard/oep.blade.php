@extends('layouts.app')

@section('title', 'OEP Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- OEP Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">OEP Dashboard</h1>
                <p class="text-indigo-100 mt-1">Overseas Employment Promoter Portal</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-indigo-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-indigo-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-600">OEP Representative</p>
    </div>

    <!-- OEP Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Candidates Assigned</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['candidates_assigned'] ?? 0) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Visa In Progress</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['visa_in_progress'] ?? 0) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-passport text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Departed</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['departed']) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-plane-departure text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pending Compliance</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['pending_compliance'] ?? 0) }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
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

    <!-- Recent Departures -->
    @if(!empty($roleData['recent_departures']) && count($roleData['recent_departures']) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Departures</h3>
            <a href="{{ route('departure.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Candidate</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">BTEVTA ID</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Departure Date</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Iqama</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">90-Day Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($roleData['recent_departures'] as $departure)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $departure->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->iqama_number)
                                <span class="badge badge-success">Registered</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->ninety_day_report_submitted)
                                <span class="badge badge-success">Submitted</span>
                            @else
                                @php
                                    $daysAgo = $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->diffInDays(now()) : 0;
                                @endphp
                                @if($daysAgo > 90)
                                    <span class="badge badge-danger">Overdue</span>
                                @elseif($daysAgo > 75)
                                    <span class="badge badge-warning">Due Soon</span>
                                @else
                                    <span class="badge badge-secondary">{{ 90 - $daysAgo }}d left</span>
                                @endif
                            @endif
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
        <a href="{{ route('visa-processing.index') }}" class="bg-white hover:bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-passport text-yellow-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Visa Processing</h4>
            <p class="text-sm text-gray-600 mt-1">Manage visa applications</p>
        </a>
        <a href="{{ route('departure.index') }}" class="bg-white hover:bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-plane-departure text-green-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Departures</h4>
            <p class="text-sm text-gray-600 mt-1">Track departures</p>
        </a>
        <a href="{{ route('reports.departure-updates') }}" class="bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-chart-line text-blue-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Post-Departure</h4>
            <p class="text-sm text-gray-600 mt-1">Salary & compliance tracking</p>
        </a>
        <a href="{{ route('candidates.index') }}" class="bg-white hover:bg-indigo-50 border-2 border-indigo-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-users text-indigo-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Candidates</h4>
            <p class="text-sm text-gray-600 mt-1">View assigned candidates</p>
        </a>
    </div>

</div>
@endsection
