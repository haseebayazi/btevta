{{-- File: resources/views/import/candidates.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Import Candidates from Excel</h2>
        <a href="{{ route('dashboard.candidates-listing') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Listing
        </a>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-semibold text-blue-900 mb-3">Import Instructions:</h3>
        <ol class="list-decimal list-inside space-y-2 text-blue-800 text-sm">
            <li>Download the BTEVTA template using the button below</li>
            <li>Fill in candidate information following the format</li>
            <li>Ensure all required fields are completed</li>
            <li>Upload the completed Excel file</li>
            <li>Review import summary and resolve any errors</li>
        </ol>
    </div>

    <!-- Download Template -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold mb-4">Step 1: Download Template</h3>
        <a href="{{ route('import.template.download') }}" 
           class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
            <i class="fas fa-download mr-2"></i>
            Download BTEVTA Import Template
        </a>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold mb-4">Step 2: Upload Completed File</h3>
        
        <form method="POST" action="{{ route('import.candidates.process') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Excel File (XLSX, XLS)
                </label>
                <input type="file" 
                       name="file" 
                       accept=".xlsx,.xls"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                       required>
                @error('file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-upload mr-2"></i>Import Candidates
                </button>
                <p class="text-sm text-gray-600">Maximum file size: 10MB</p>
            </div>
        </form>
    </div>

    <!-- Import Errors (if any) -->
    @if(session('import_errors'))
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h3 class="font-semibold text-red-900 mb-3">Import Errors:</h3>
        <div class="max-h-96 overflow-y-auto">
            <ul class="space-y-1 text-sm text-red-800">
                @foreach(session('import_errors') as $error)
                <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Available Trades -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold mb-4">Available Trades</h3>
        <p class="text-sm text-gray-600 mb-3">Use <strong>ID</strong>, <strong>Code</strong>, or <strong>Name</strong> in the "Trade" column of your import file:</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
            @forelse($trades as $trade)
            <div class="border border-gray-200 rounded px-3 py-2">
                <span class="font-mono text-xs bg-gray-100 text-gray-700 px-1 rounded">ID: {{ $trade->id }}</span>
                <span class="font-mono text-sm font-semibold text-blue-600 ml-1">{{ $trade->code }}</span>
                <span class="text-sm text-gray-600">- {{ $trade->name }}</span>
            </div>
            @empty
            <p class="text-sm text-red-600 col-span-full">No active trades found. Please add trades in Admin panel first.</p>
            @endforelse
        </div>
    </div>

    <!-- Template Format Guide -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold mb-4">Template Format Guide</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium">Column</th>
                        <th class="px-4 py-2 text-left font-medium">Required</th>
                        <th class="px-4 py-2 text-left font-medium">Format</th>
                        <th class="px-4 py-2 text-left font-medium">Example</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-2">BTEVTA ID</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">Unique ID</td>
                        <td class="px-4 py-2 text-gray-600">BTEVTA-2025-001</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">CNIC</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">13 digits</td>
                        <td class="px-4 py-2 text-gray-600">3520112345678</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Name</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">Text</td>
                        <td class="px-4 py-2 text-gray-600">Muhammad Ahmed</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Date of Birth</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">YYYY-MM-DD</td>
                        <td class="px-4 py-2 text-gray-600">1995-05-15</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Gender</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">male/female/other</td>
                        <td class="px-4 py-2 text-gray-600">male</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Trade</td>
                        <td class="px-4 py-2 text-red-600">Yes</td>
                        <td class="px-4 py-2">ID, Code, or Name</td>
                        <td class="px-4 py-2 text-gray-600">1, TRD-ELC, or Electrician</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection