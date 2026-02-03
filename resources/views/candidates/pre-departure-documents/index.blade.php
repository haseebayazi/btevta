@extends('layouts.app')

@section('title', 'Pre-Departure Documents - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('candidates.index') }}" class="hover:text-blue-600">Candidates</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('candidates.show', $candidate) }}" class="hover:text-blue-600">{{ $candidate->name }}</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">Documents</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Pre-Departure Documents</h1>
                <p class="text-gray-600 mt-1">Manage required documents for {{ $candidate->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('candidates.show', $candidate) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Candidate
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Candidate Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Candidate</p>
                    <p class="font-semibold text-gray-900 truncate">{{ $candidate->name }}</p>
                </div>
            </div>
        </div>

        <!-- BTEVTA ID -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-id-card text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">TheLeap ID</p>
                    <p class="font-semibold text-gray-900">{{ $candidate->btevta_id ?? 'Not Assigned' }}</p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 {{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'bg-green-100' : 'bg-gray-100' }} rounded-full flex items-center justify-center">
                    <i class="fas fa-{{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'edit' : 'lock' }} {{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'text-green-600' : 'text-gray-600' }} text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="font-semibold {{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'text-green-600' : 'text-gray-600' }}">
                        {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 {{ $status['is_complete'] ? 'bg-green-100' : 'bg-yellow-100' }} rounded-full flex items-center justify-center">
                    <i class="fas fa-{{ $status['is_complete'] ? 'check-circle' : 'clock' }} {{ $status['is_complete'] ? 'text-green-600' : 'text-yellow-600' }} text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Documents</p>
                    <p class="font-semibold {{ $status['is_complete'] ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $status['mandatory_uploaded'] }}/{{ $status['mandatory_total'] }} ({{ $status['completion_percentage'] }}%)
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- CRITICAL Alert if seeder hasn't been run --}}
    @if(isset($status['seeder_required']) && $status['seeder_required'])
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Configuration Required:</strong> Document checklists have not been initialized.
        <br>
        <strong>To fix:</strong> Run <code>php artisan db:seed --class=DocumentChecklistsSeeder</code>
        <br>
        <small class="text-muted">This will create the mandatory document checklist items (CNIC, Passport, Domicile, FRC, PCC).</small>
    </div>
    @elseif(!$status['is_complete'])
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Action Required:</strong> This candidate cannot proceed to screening until all mandatory documents are uploaded.
        @if($candidate->getMissingMandatoryDocuments()->isNotEmpty())
            Missing: <strong>{{ $candidate->getMissingMandatoryDocuments()->pluck('name')->implode(', ') }}</strong>
        @endif
    </div>
    @else
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">All Documents Complete</h3>
                <p class="mt-1 text-sm text-green-700">All mandatory pre-departure documents have been uploaded. The candidate is ready to proceed.</p>
            </div>
        </div>
    </div>
    @endif

    @if(!in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']))
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-lock text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Read-Only Mode</h3>
                <p class="mt-1 text-sm text-blue-700">Documents cannot be edited because the candidate has progressed past the document collection phase.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center justify-between" id="success-alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
        <button onclick="document.getElementById('success-alert').remove()" class="text-green-600 hover:text-green-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    </div>
    @endif

    <!-- Mandatory Documents Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-600 rounded-t-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Mandatory Documents</h2>
                        <p class="text-red-100 text-sm">Required for screening progression</p>
                    </div>
                </div>
                <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm font-medium">
                    {{ $status['mandatory_uploaded'] }}/{{ $status['mandatory_total'] }} uploaded
                </span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($checklists->where('is_mandatory', true) as $checklist)
                    @php
                        $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                    @endphp
                    @include('candidates.pre-departure-documents.partials.document-card', [
                        'checklist' => $checklist,
                        'document' => $document,
                        'candidate' => $candidate
                    ])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Optional Documents Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-folder-plus text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Optional Documents</h2>
                        <p class="text-blue-100 text-sm">Additional supporting documents</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($checklists->where('is_mandatory', false) as $checklist)
                    @php
                        $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                    @endphp
                    @include('candidates.pre-departure-documents.partials.document-card', [
                        'checklist' => $checklist,
                        'document' => $document,
                        'candidate' => $candidate
                    ])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Licenses Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-500 to-purple-600 rounded-t-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-id-card text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Licenses</h2>
                        <p class="text-purple-100 text-sm">Driving & Professional Licenses</p>
                    </div>
                </div>
                @can('create', [App\Models\CandidateLicense::class, $candidate])
                <button onclick="openLicenseModal()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>Add License</span>
                </button>
                @endcan
            </div>
        </div>
        <div class="p-6">
            @if($licenses->isEmpty())
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-id-card text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-gray-500 font-medium">No Licenses Added</h3>
                <p class="text-gray-400 text-sm mt-1">Add driving or professional licenses for this candidate</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Number</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Issue Date</th>
                            <th class="px-4 py-3">Expiry Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($licenses as $license)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $license->license_type === 'driving' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas fa-{{ $license->license_type === 'driving' ? 'car' : 'certificate' }} mr-1"></i>
                                    {{ ucfirst($license->license_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-medium text-gray-900">{{ $license->license_name }}</td>
                            <td class="px-4 py-4 text-gray-600 font-mono text-sm">{{ $license->license_number }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $license->license_category ?? '-' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $license->issue_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $license->expiry_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-4">
                                @if($license->isExpired())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Expired
                                    </span>
                                @elseif($license->isExpiringSoon())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Expiring Soon
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @can('delete', $license)
                                <form action="{{ route('candidates.licenses.destroy', [$candidate, $license]) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this license?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- Report Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-download text-gray-600 text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Generate Reports</h2>
                    <p class="text-gray-500 text-sm">Download document reports in various formats</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('reports.pre-departure.individual', ['candidate' => $candidate, 'format' => 'pdf']) }}"
                   class="inline-flex items-center px-5 py-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition border border-red-200">
                    <i class="fas fa-file-pdf text-xl mr-3"></i>
                    <div class="text-left">
                        <div class="font-medium">PDF Report</div>
                        <div class="text-xs text-red-500">Download as PDF</div>
                    </div>
                </a>
                <a href="{{ route('reports.pre-departure.individual', ['candidate' => $candidate, 'format' => 'excel']) }}"
                   class="inline-flex items-center px-5 py-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition border border-green-200">
                    <i class="fas fa-file-excel text-xl mr-3"></i>
                    <div class="text-left">
                        <div class="font-medium">Excel Report</div>
                        <div class="text-xs text-green-500">Download as Excel</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Add License Modal --}}
@include('candidates.pre-departure-documents.partials.add-license-modal')
@endsection

@push('scripts')
<script>
function openLicenseModal() {
    document.getElementById('addLicenseModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeLicenseModal() {
    document.getElementById('addLicenseModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Auto-hide success alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.remove();
            }, 500);
        }, 5000);
    }
});
</script>
@endpush
