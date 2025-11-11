@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- WASL Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ config('app.full_name') }}</h1>
                <p class="text-blue-100 mt-1">{{ config('app.tagline') }}</p>
                <p class="text-blue-200 text-sm mt-1">{{ config('app.subtitle') }}</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-blue-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-blue-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div>
            <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
            <p class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }} Dashboard</p>
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
    
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Candidates -->
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
        
        <!-- In Training -->
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
        
        <!-- Visa Processing -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Visa Processing</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['visa_processing']) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-passport text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Departed -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Departed</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['departed']) }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-plane-departure text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Process Flow Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Process Pipeline -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Candidate Pipeline</h3>
            
            <div class="space-y-4">
                <!-- Listed -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Listed</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['listed']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['listed'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Screening -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Screening</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['screening']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['screening'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Registered -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Registered</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['registered']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-cyan-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['registered'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Training -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">In Training</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['in_training']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['in_training'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Visa Processing -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Visa Processing</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['visa_processing']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['visa_processing'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Departed -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Departed</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['departed']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $stats['total_candidates'] > 0 ? ($stats['departed'] / $stats['total_candidates'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Stats</h3>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Active Batches</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['active_batches']) }}</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Pending Complaints</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['pending_complaints']) }}</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-yellow-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Pending Reply</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['pending_correspondence']) }}</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-gray-600 mr-3"></i>
                        <span class="text-sm text-gray-700">Rejected</span>
                    </div>
                    <span class="font-bold text-gray-900">{{ number_format($stats['rejected']) }}</span>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Activities</h3>
            <a href="{{ route('admin.audit-logs') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-sm"></i>
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
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('import.candidates.form') }}" class="bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-file-import text-blue-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Import Candidates</h4>
            <p class="text-sm text-gray-600 mt-1">Bulk import from Excel</p>
        </a>
        
        <a href="{{ route('candidates.create') }}" class="bg-white hover:bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-user-plus text-green-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Add Candidate</h4>
            <p class="text-sm text-gray-600 mt-1">Register new candidate</p>
        </a>
        
        <a href="{{ route('reports.index') }}" class="bg-white hover:bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-chart-bar text-purple-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Generate Report</h4>
            <p class="text-sm text-gray-600 mt-1">View analytics</p>
        </a>
        
        <a href="{{ route('complaints.create') }}" class="bg-white hover:bg-red-50 border-2 border-red-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-exclamation-circle text-red-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Register Complaint</h4>
            <p class="text-sm text-gray-600 mt-1">Submit new complaint</p>
        </a>
    </div>
    
</div>
@endsection