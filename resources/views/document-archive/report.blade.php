@extends('layouts.app')
@section('title', 'Document Archive Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Document Archive Report</h1>
            <p class="text-gray-600 mt-2">
                Report Period: {{ \Carbon\Carbon::parse(request('start_date'))->format('M d, Y') }} -
                {{ \Carbon\Carbon::parse(request('end_date'))->format('M d, Y') }}
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print mr-2"></i>Print
            </button>
            <a href="{{ route('document-archive.index') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Documents</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $report['total_documents'] ?? 0 }}</p>
        </div>
        <div class="card bg-green-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">New Uploads</h3>
            <p class="text-3xl font-bold text-green-600">{{ $report['new_uploads'] ?? 0 }}</p>
        </div>
        <div class="card bg-yellow-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Expiring Soon</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $report['expiring_soon'] ?? 0 }}</p>
        </div>
        <div class="card bg-red-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Expired</h3>
            <p class="text-3xl font-bold text-red-600">{{ $report['expired'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Storage Statistics -->
    <div class="card mb-6">
        <h3 class="text-lg font-bold mb-4">Storage Statistics</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded">
                <p class="text-2xl font-bold text-gray-700">
                    {{ number_format(($report['total_size'] ?? 0) / 1024 / 1024, 2) }} MB
                </p>
                <p class="text-sm text-gray-600">Total Storage Used</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded">
                <p class="text-2xl font-bold text-gray-700">
                    {{ number_format(($report['average_size'] ?? 0) / 1024, 2) }} KB
                </p>
                <p class="text-sm text-gray-600">Average File Size</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded">
                <p class="text-2xl font-bold text-gray-700">{{ $report['total_downloads'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Total Downloads</p>
            </div>
        </div>
    </div>

    <!-- Documents by Category -->
    <div class="card mb-6">
        <h3 class="text-lg font-bold mb-4">Documents by Category</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size (MB)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($report['by_category'] ?? [] as $category => $data)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold">{{ ucfirst($category) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $data['count'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ number_format(($data['size'] ?? 0) / 1024 / 1024, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ $data['percentage'] ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm">{{ number_format($data['percentage'] ?? 0, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Documents by Type -->
    <div class="card mb-6">
        <h3 class="text-lg font-bold mb-4">Documents by Type</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Size (MB)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($report['by_type'] ?? [] as $type => $data)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge badge-secondary">{{ strtoupper($type) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $data['count'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ number_format(($data['size'] ?? 0) / 1024 / 1024, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Uploaders -->
    @if(isset($report['top_uploaders']))
    <div class="card mb-6">
        <h3 class="text-lg font-bold mb-4">Top Uploaders</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documents</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Size (MB)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($report['top_uploaders'] as $uploader)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $uploader['name'] ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $uploader['count'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ number_format(($uploader['size'] ?? 0) / 1024 / 1024, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Uploads -->
    @if(isset($report['recent_uploads']))
    <div class="card mb-6">
        <h3 class="text-lg font-bold mb-4">Recent Uploads</h3>
        <div class="space-y-2">
            @foreach($report['recent_uploads'] as $document)
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                <div class="flex-1">
                    <p class="font-semibold">{{ $document['name'] ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-600">
                        {{ $document['category'] ?? 'N/A' }} |
                        {{ $document['type'] ?? 'N/A' }} |
                        {{ number_format(($document['size'] ?? 0) / 1024, 2) }} KB
                    </p>
                </div>
                <div class="text-sm text-gray-600">
                    {{ isset($document['uploaded_at']) ? \Carbon\Carbon::parse($document['uploaded_at'])->format('M d, Y') : 'N/A' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Expiry Alerts -->
    @if(isset($report['expiring_documents']) && count($report['expiring_documents']) > 0)
    <div class="card border-l-4 border-yellow-500">
        <h3 class="text-lg font-bold mb-4 text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>Documents Expiring Soon
        </h3>
        <div class="space-y-2">
            @foreach($report['expiring_documents'] as $document)
            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded">
                <div class="flex-1">
                    <p class="font-semibold">{{ $document['name'] ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-600">{{ $document['category'] ?? 'N/A' }}</p>
                </div>
                <div class="text-sm font-semibold text-yellow-700">
                    Expires: {{ isset($document['expiry_date']) ? \Carbon\Carbon::parse($document['expiry_date'])->format('M d, Y') : 'N/A' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Report Footer -->
    <div class="card mt-6 bg-gray-50">
        <div class="text-center text-sm text-gray-600">
            <p>Report generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
            <p>Generated by {{ auth()->user()->name }} ({{ auth()->user()->email }})</p>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, nav, .no-print {
        display: none !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>
@endsection
