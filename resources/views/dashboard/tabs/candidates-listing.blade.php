@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Candidates Listing</h2>
        <div class="flex space-x-3">
            <a href="{{ route('import.candidates.form') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-file-import mr-2"></i>Import from Excel
            </a>
            <a href="{{ route('candidates.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add Candidate
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" name="search" placeholder="Search by Name, CNIC, BTEVTA ID" 
                   value="{{ request('search') }}" class="px-4 py-2 border rounded-lg">
            
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">All Status</option>
                <option value="listed" {{ request('status') === 'listed' ? 'selected' : '' }}>Listed</option>
                <option value="screening" {{ request('status') === 'screening' ? 'selected' : '' }}>Screening</option>
                <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Registered</option>
                <option value="training" {{ request('status') === 'training' ? 'selected' : '' }}>Training</option>
                <option value="visa_processing" {{ request('status') === 'visa_processing' ? 'selected' : '' }}>Visa</option>
                <option value="departed" {{ request('status') === 'departed' ? 'selected' : '' }}>Departed</option>
            </select>
            
            <select name="trade_id" class="px-4 py-2 border rounded-lg">
                <option value="">All Trades</option>
                @foreach($trades as $id => $trade)
                    <option value="{{ $id }}" {{ request('trade_id') == $id ? 'selected' : '' }}>{{ $trade }}</option>
                @endforeach
            </select>
            
            <select name="batch_id" class="px-4 py-2 border rounded-lg">
                <option value="">All Batches</option>
                @foreach($batches as $id => $batch)
                    <option value="{{ $id }}" {{ request('batch_id') == $id ? 'selected' : '' }}>{{ $batch }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="overflow-x-auto">
            @if($candidates->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">BTEVTA ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">CNIC</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $candidate)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $candidate->name }}</td>
                                <td class="px-6 py-4 font-mono">{{ $candidate->btevta_id }}</td>
                                <td class="px-6 py-4 font-mono">{{ $candidate->cnic ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $candidate->campus->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $candidate->trade->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                        @if($candidate->status === 'listed') bg-blue-100 text-blue-800
                                        @elseif($candidate->status === 'screening') bg-yellow-100 text-yellow-800
                                        @elseif($candidate->status === 'registered') bg-green-100 text-green-800
                                        @elseif($candidate->status === 'training') bg-purple-100 text-purple-800
                                        @elseif($candidate->status === 'visa_processing') bg-indigo-100 text-indigo-800
                                        @elseif($candidate->status === 'departed') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800
                                        @endif
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('candidates.show', $candidate->id) }}" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">View</a>
                                        <a href="{{ route('candidates.edit', $candidate->id) }}" 
                                           class="text-green-600 hover:text-green-900 text-sm font-medium">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="mt-4">
                    {{ $candidates->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No candidates found</p>
            @endif
        </div>
    </div>
</div>
@endsection