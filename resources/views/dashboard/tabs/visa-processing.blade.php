@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Visa Processing Pipeline</h2>
        <a href="{{ route('visa-processing.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Visa Record
        </a>
    </div>

    <!-- Visa Stage Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-gray-600 text-sm">Interview</p>
            <p class="text-2xl font-bold text-blue-600 mt-2">{{ $visaStats['interview'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-gray-600 text-sm">Trade Test</p>
            <p class="text-2xl font-bold text-indigo-600 mt-2">{{ $visaStats['trade_test'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-gray-600 text-sm">Medical</p>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ $visaStats['medical'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-gray-600 text-sm">Biometric</p>
            <p class="text-2xl font-bold text-purple-600 mt-2">{{ $visaStats['biometric'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-gray-600 text-sm">Visa Issued</p>
            <p class="text-2xl font-bold text-orange-600 mt-2">{{ $visaStats['visa_issued'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Visa Processing Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Candidates in Visa Processing</h3>
        
        <div class="overflow-x-auto">
            @if($visaProcessing->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">BTEVTA ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">OEP</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Interview Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Medical Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Progress</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visaProcessing as $visa)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $visa->candidate->name }}</td>
                                <td class="px-6 py-4 font-mono">{{ $visa->candidate->btevta_id }}</td>
                                <td class="px-6 py-4">{{ $visa->oep->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $visa->interview_date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $visa->medical_date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $progressItems = 0;
                                        if($visa->interview_completed) $progressItems++;
                                        if($visa->trade_test_completed) $progressItems++;
                                        if($visa->medical_completed) $progressItems++;
                                        if($visa->biometric_completed) $progressItems++;
                                        if($visa->visa_issued) $progressItems++;
                                    @endphp
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" 
                                             style="width: {{ ($progressItems / 5) * 100 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ $progressItems }}/5</span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('visa-processing.show', $visa->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $visaProcessing->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No candidates in visa processing</p>
            @endif
        </div>
    </div>
</div>
@endsection