@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Document Archive</h2>
        <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-upload mr-2"></i>Upload Document
        </button>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Total Documents</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $docStats['total_documents'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Expiring Soon (30 days)</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $docStats['expiring_soon'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Expired</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $docStats['expired'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Search documents..." 
                   value="{{ request('search') }}" class="px-4 py-2 border rounded-lg">
            
            <select name="document_type" class="px-4 py-2 border rounded-lg">
                <option value="">All Document Types</option>
                <option value="cnic" {{ request('document_type') === 'cnic' ? 'selected' : '' }}>CNIC</option>
                <option value="passport" {{ request('document_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                <option value="medical" {{ request('document_type') === 'medical' ? 'selected' : '' }}>Medical</option>
                <option value="clearance" {{ request('document_type') === 'clearance' ? 'selected' : '' }}>Clearance</option>
                <option value="certificate" {{ request('document_type') === 'certificate' ? 'selected' : '' }}>Certificate</option>
            </select>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>

    <!-- Documents Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Documents</h3>
        
        <div class="overflow-x-auto">
            @if($documents->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Document</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Candidate</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Upload Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Expiry Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                            @php
                                $status = 'Valid';
                                $statusClass = 'bg-green-100 text-green-800';
                                
                                if($doc->expiry_date && $doc->expiry_date < now()) {
                                    $status = 'Expired';
                                    $statusClass = 'bg-red-100 text-red-800';
                                } elseif($doc->expiry_date && $doc->expiry_date <= now()->addDays(30)) {
                                    $status = 'Expiring Soon';
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                }
                            @endphp
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $doc->document_name }}</td>
                                <td class="px-6 py-4">{{ $doc->candidate->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                        {{ ucfirst($doc->document_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $doc->created_at->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">{{ $doc->expiry_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('document-archive.show', $doc->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No documents found</p>
            @endif
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Upload Document</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('document-archive.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Candidate *</label>
                    <select name="candidate_id" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">Select Candidate</option>
                        @foreach(\App\Models\Candidate::select('id', 'name', 'btevta_id')->orderBy('name')->get() as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->btevta_id }} - {{ $candidate->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type *</label>
                    <select name="document_type" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">Select Type</option>
                        <option value="cnic">CNIC</option>
                        <option value="passport">Passport</option>
                        <option value="medical">Medical Certificate</option>
                        <option value="clearance">Police Clearance</option>
                        <option value="certificate">Training Certificate</option>
                        <option value="visa">Visa</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Name *</label>
                    <input type="text" name="document_name" class="w-full px-3 py-2 border rounded-lg"
                           placeholder="e.g., Training Certificate 2024" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date (if applicable)</label>
                    <input type="date" name="expiry_date" class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload File *</label>
                    <input type="file" name="file" class="w-full px-3 py-2 border rounded-lg"
                           accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="text-xs text-gray-500 mt-1">Max 5MB. Supported: PDF, JPG, PNG</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        onclick="document.getElementById('uploadModal').classList.add('hidden')"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-upload mr-2"></i>Upload
                </button>
            </div>
        </form>
    </div>
</div>
@endsection