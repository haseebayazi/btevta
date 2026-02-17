@extends('layouts.app')

@section('title', 'Visa Processing')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Visa Processing</h2>
            <p class="text-gray-500 text-sm mt-1">Manage candidates in visa processing pipeline</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('visa-processing.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> New Visa Process
            </a>
            <a href="{{ route('visa-processing.hierarchical-dashboard') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-th-large mr-1"></i> Stage Dashboard
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if($message = Session::get('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-check-circle mr-2"></i>{{ $message }}</span>
        <button @click="show = false" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if($message = Session::get('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}</span>
        <button @click="show = false" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
    </div>
    @endif

    {{-- Candidates Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h5 class="font-semibold text-gray-800">Candidates in Visa Processing</h5>
        </div>
        <div class="p-5">
            @if($candidates->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">TheLeap ID</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">OEP</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Current Stage</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Interview</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Trade Test</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Medical</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Visa Status</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($candidates as $candidate)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $candidate->btevta_id }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $candidate->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $candidate->oep?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($candidate->visaProcess)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                {{ ucfirst(str_replace('_', ' ', $candidate->visaProcess->overall_status ?? 'Initiated')) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not Started</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php $iSt = $candidate->visaProcess?->interview_status; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $iSt === 'completed' || $iSt === 'passed' ? 'bg-green-100 text-green-700' : ($iSt === 'failed' ? 'bg-red-100 text-red-700' : ($iSt === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                                            {{ $iSt ? ucfirst($iSt) : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php $ttSt = $candidate->visaProcess?->trade_test_status; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $ttSt === 'completed' || $ttSt === 'passed' ? 'bg-green-100 text-green-700' : ($ttSt === 'failed' ? 'bg-red-100 text-red-700' : ($ttSt === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                                            {{ $ttSt ? ucfirst($ttSt) : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php $mSt = $candidate->visaProcess?->medical_status; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mSt === 'completed' || $mSt === 'fit' ? 'bg-green-100 text-green-700' : ($mSt === 'unfit' || $mSt === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">
                                            {{ $mSt ? ucfirst($mSt) : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($candidate->visaProcess?->visa_number)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                Issued
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            <a href="{{ route('visa-processing.show', $candidate) }}" class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View Details">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            @if($candidate->visaProcess)
                                            <a href="{{ route('visa-processing.edit', $candidate) }}" class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 p-1.5 rounded" title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $candidates->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-passport text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No candidates in visa processing.</p>
                    <a href="{{ route('visa-processing.create') }}" class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block">Start now</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
