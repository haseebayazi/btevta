@extends('layouts.admin')

@section('title', 'Employer Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Employer Dashboard</h1>
            <p class="text-gray-600 mt-1">Overview of employer statistics and management</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.employers.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                <i class="fas fa-list"></i> Employer List
            </a>
            @can('create', App\Models\Employer::class)
            <a href="{{ route('admin.employers.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus"></i> Add Employer
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
            <div class="text-sm text-gray-600">Total</div>
            <div class="text-2xl font-bold text-gray-800">{{ $dashboard['summary']['total'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
            <div class="text-sm text-gray-600">Active</div>
            <div class="text-2xl font-bold text-green-600">{{ $dashboard['summary']['active'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-indigo-500">
            <div class="text-sm text-gray-600">Verified</div>
            <div class="text-2xl font-bold text-indigo-600">{{ $dashboard['summary']['verified'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
            <div class="text-sm text-gray-600">Unverified</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $dashboard['summary']['unverified'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-orange-500">
            <div class="text-sm text-gray-600">Expiring</div>
            <div class="text-2xl font-bold text-orange-600">{{ $dashboard['summary']['with_expiring_permission'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
            <div class="text-sm text-gray-600">Expired</div>
            <div class="text-2xl font-bold text-red-600">{{ $dashboard['summary']['with_expired_permission'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- By Country -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-globe"></i> Employers by Country
            </h2>
            @if($dashboard['by_country']->count() > 0)
                <div class="space-y-3">
                    @foreach($dashboard['by_country'] as $country => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">{{ $country }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $dashboard['summary']['total'] > 0 ? ($count / $dashboard['summary']['total'] * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 w-8 text-right">{{ $count }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No data available</p>
            @endif
        </div>

        <!-- By Sector -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-industry"></i> Employers by Sector
            </h2>
            @if($dashboard['by_sector']->count() > 0)
                <div class="space-y-3">
                    @foreach($dashboard['by_sector'] as $sector => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">{{ $sector }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2.5">
                                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $dashboard['summary']['total'] > 0 ? ($count / $dashboard['summary']['total'] * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 w-8 text-right">{{ $count }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No sector data available</p>
            @endif
        </div>
    </div>

    <!-- Top Employers -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-trophy"></i> Top Employers by Active Candidates
        </h2>
        @if($dashboard['top_employers']->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sector</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active Candidates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Candidates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verified</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($dashboard['top_employers'] as $emp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $emp->visa_issuing_company }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $emp->country?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $emp->sector ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-blue-600">{{ $emp->active_candidates_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $emp->total_candidates_count }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($emp->verified)
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs"><i class="fas fa-check"></i> Yes</span>
                            @else
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.employers.show', $emp) }}" class="text-blue-600 hover:text-blue-900">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-gray-500 text-sm">No employers with candidates yet.</p>
        @endif
    </div>

    <!-- Expiring & Expired Permissions -->
    @if($dashboard['expiring_permissions']->count() > 0 || $dashboard['expired_permissions']->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-exclamation-triangle text-yellow-600"></i> Permission Alerts
        </h2>

        @if($dashboard['expired_permissions']->count() > 0)
        <h3 class="text-md font-semibold text-red-700 mb-2">Expired Permissions</h3>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-red-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Permission #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expired On</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard['expired_permissions'] as $emp)
                    <tr class="bg-red-50">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $emp->visa_issuing_company }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $emp->permission_number ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-red-600 font-semibold">{{ $emp->permission_expiry_date->format('M d, Y') }}</td>
                        <td class="px-4 py-2 text-sm">
                            <a href="{{ route('admin.employers.edit', $emp) }}" class="text-blue-600 hover:text-blue-900">Update</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($dashboard['expiring_permissions']->count() > 0)
        <h3 class="text-md font-semibold text-yellow-700 mb-2">Expiring Soon (within 30 days)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-yellow-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Permission #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expires On</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard['expiring_permissions'] as $emp)
                    <tr class="bg-yellow-50">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $emp->visa_issuing_company }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $emp->permission_number ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-yellow-600 font-semibold">{{ $emp->permission_expiry_date->format('M d, Y') }}</td>
                        <td class="px-4 py-2 text-sm text-yellow-600 font-bold">{{ (int) now()->diffInDays($emp->permission_expiry_date) }} days</td>
                        <td class="px-4 py-2 text-sm">
                            <a href="{{ route('admin.employers.edit', $emp) }}" class="text-blue-600 hover:text-blue-900">Update</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    <!-- Recent Employers -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-clock"></i> Recently Added Employers
        </h2>
        @if($dashboard['recent_employers']->count() > 0)
        <div class="space-y-3">
            @foreach($dashboard['recent_employers'] as $emp)
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                <div>
                    <a href="{{ route('admin.employers.show', $emp) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        {{ $emp->visa_issuing_company }}
                    </a>
                    <p class="text-sm text-gray-500">{{ $emp->country?->name }} {{ $emp->sector ? '- ' . $emp->sector : '' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">{{ $emp->created_at->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-500">{{ $emp->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-gray-500 text-sm">No employers added yet.</p>
        @endif
    </div>
</div>
@endsection
