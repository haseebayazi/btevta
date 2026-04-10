@extends('layouts.app')

@section('title', 'Employer Details - ' . $employer->visa_issuing_company)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $employer->visa_issuing_company }}</h1>
                <p class="text-gray-600 mt-1">Employer Details and Management</p>
            </div>
            <div class="flex gap-3">
                @can('update', $employer)
                <a href="{{ route('admin.employers.edit', $employer) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @endcan
                <a href="{{ route('admin.employers.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    Back to List
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Status Badges -->
    <div class="mb-6 flex flex-wrap gap-2">
        @if($employer->is_active)
            <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                <i class="fas fa-check-circle"></i> Active
            </span>
        @else
            <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                <i class="fas fa-times-circle"></i> Inactive
            </span>
        @endif

        @if($employer->verified)
            <span class="inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                <i class="fas fa-shield-alt"></i> Verified
                @if($employer->verified_at)
                    ({{ $employer->verified_at->format('M d, Y') }})
                @endif
            </span>
        @else
            <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                <i class="fas fa-exclamation-triangle"></i> Unverified
            </span>
            @can('verify', $employer)
                <form action="{{ route('admin.employers.verify', $employer) }}" method="POST" class="inline"
                      onsubmit="return confirm('Are you sure you want to verify this employer?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-semibold hover:bg-blue-700 transition-colors">
                        <i class="fas fa-check"></i> Verify Now
                    </button>
                </form>
            @endcan
        @endif

        @if($employer->permission_expiring)
            <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                <i class="fas fa-clock"></i> Permission Expiring Soon
            </span>
        @endif

        @if($employer->permission_expired)
            <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                <i class="fas fa-ban"></i> Permission Expired
            </span>
        @endif

        @if($employer->company_size)
            <span class="inline-block px-4 py-2 bg-{{ $employer->company_size->color() }}-100 text-{{ $employer->company_size->color() }}-800 rounded-full text-sm font-semibold">
                {{ $employer->company_size->label() }}
            </span>
        @endif
    </div>

    <!-- Basic Information Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Basic Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Permission Number</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->permission_number ?? 'Not specified' }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Visa Issuing Company</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->visa_issuing_company }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Visa Company License</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->visa_company_license ?? 'Not specified' }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Country</label>
                <p class="text-gray-800 font-semibold text-lg">
                    @if($employer->country)
                        {{ $employer->country->flag_emoji ? $employer->country->flag_emoji . ' ' : '' }}{{ $employer->country->name }}
                    @else
                        <span class="text-gray-400">Not specified</span>
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">City</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->city ?? 'Not specified' }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Sector</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->sector ?? 'Not specified' }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Trade/Occupation</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->trade ?? 'Not specified' }}</p>
            </div>

            @if($employer->tradeRelation)
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Linked Trade</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->tradeRelation->name }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Permission Details Section -->
    @if($employer->permission_number || $employer->permission_issue_date || $employer->permission_expiry_date)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Permission Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Issue Date</label>
                <p class="text-gray-800">{{ $employer->permission_issue_date?->format('M d, Y') ?? 'Not set' }}</p>
            </div>
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Expiry Date</label>
                <p class="text-gray-800">
                    {{ $employer->permission_expiry_date?->format('M d, Y') ?? 'Not set' }}
                    @if($employer->permission_expiring)
                        <span class="text-yellow-600 text-sm ml-2"><i class="fas fa-exclamation-triangle"></i> Expiring soon</span>
                    @endif
                    @if($employer->permission_expired)
                        <span class="text-red-600 text-sm ml-2"><i class="fas fa-times-circle"></i> Expired</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Permission Document</label>
                @if($employer->permission_document_path)
                    <p class="text-blue-600">
                        <i class="fas fa-file-alt"></i> {{ basename($employer->permission_document_path) }}
                    </p>
                @else
                    <p class="text-gray-400">No document uploaded</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Employment Package Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Employment Package</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Basic Salary</label>
                <p class="text-gray-800 font-semibold text-2xl">
                    @if($employer->basic_salary)
                        {{ number_format($employer->basic_salary, 2) }}
                        <span class="text-lg text-gray-600">{{ $employer->salary_currency ?? 'PKR' }}</span>
                    @else
                        <span class="text-gray-400 text-lg">Not specified</span>
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Benefits Provided</label>
                <div class="space-y-2 mt-2">
                    @foreach([['food_by_company', 'Food'], ['accommodation_by_company', 'Accommodation'], ['transport_by_company', 'Transport']] as [$field, $label])
                    <div class="flex items-center gap-2">
                        @if($employer->$field)
                            <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm"><i class="fas fa-check"></i></span>
                            <span class="text-gray-800">{{ $label }}</span>
                        @else
                            <span class="w-6 h-6 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center text-sm"><i class="fas fa-times"></i></span>
                            <span class="text-gray-400">{{ $label }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($employer->other_conditions)
        <div>
            <label class="block text-gray-500 text-sm font-medium mb-2">Other Conditions & Terms</label>
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                <p class="text-gray-800 whitespace-pre-line">{{ $employer->other_conditions }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Default Package Breakdown -->
    @if($employer->default_package)
    @php $pkg = $employer->default_package_object; @endphp
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Default Employment Package Breakdown</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            @foreach($pkg->getBreakdown() as $item)
            <div class="bg-gray-50 rounded-md p-4">
                <div class="text-sm text-gray-500">{{ $item['label'] }}</div>
                <div class="text-xl font-bold text-gray-800">{{ number_format($item['amount'], 2) }} {{ $pkg->currency }}</div>
                @if($item['percentage'] > 0)
                <div class="text-xs text-gray-500">{{ $item['percentage'] }}% of total</div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-blue-800">Total Package</span>
                <span class="text-2xl font-bold text-blue-800">{{ $pkg->getFormattedTotal() }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Package Management Form -->
    @can('update', $employer)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-money-bill-wave"></i> Update Default Package
        </h2>

        <form action="{{ route('admin.employers.set-package', $employer) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Base Salary <span class="text-red-500">*</span></label>
                    <input type="number" name="base_salary" step="0.01" min="0" required
                           value="{{ $employer->default_package_object->baseSalary ?: '' }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Currency <span class="text-red-500">*</span></label>
                    <select name="currency" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['SAR', 'AED', 'OMR', 'QAR', 'BHD', 'KWD', 'USD', 'PKR'] as $c)
                            <option value="{{ $c }}" {{ $employer->default_package_object->currency === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Housing Allowance</label>
                    <input type="number" name="housing_allowance" step="0.01" min="0"
                           value="{{ $employer->default_package_object->housingAllowance ?: '' }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Food Allowance</label>
                    <input type="number" name="food_allowance" step="0.01" min="0"
                           value="{{ $employer->default_package_object->foodAllowance ?: '' }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Transport Allowance</label>
                    <input type="number" name="transport_allowance" step="0.01" min="0"
                           value="{{ $employer->default_package_object->transportAllowance ?: '' }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Other Allowance</label>
                    <input type="number" name="other_allowance" step="0.01" min="0"
                           value="{{ $employer->default_package_object->otherAllowance ?: '' }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <i class="fas fa-save"></i> Save Package
            </button>
        </form>
    </div>
    @endcan

    <!-- Documents Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-folder-open"></i> Documents
        </h2>

        {{-- Existing Evidence --}}
        @if($employer->evidence_path)
        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
            <div>
                <p class="text-gray-800 font-medium"><i class="fas fa-file"></i> {{ basename($employer->evidence_path) }}</p>
                <p class="text-sm text-gray-600 mt-1">Main evidence document</p>
            </div>
            <a href="{{ route('admin.employers.download-evidence', $employer) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
        @endif

        {{-- Employer Documents Table --}}
        @if($employer->documents->count() > 0)
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($employer->documents as $doc)
                    <tr>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">
                                {{ \App\Models\EmployerDocument::documentTypes()[$doc->document_type] ?? ucfirst($doc->document_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $doc->document_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->document_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->issue_date?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            {{ $doc->expiry_date?->format('M d, Y') ?? '-' }}
                            @if($doc->isExpired())
                                <span class="text-red-600 text-xs ml-1"><i class="fas fa-times-circle"></i> Expired</span>
                            @elseif($doc->isExpiring())
                                <span class="text-yellow-600 text-xs ml-1"><i class="fas fa-exclamation-triangle"></i> Expiring</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($doc->isExpired())
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Expired</span>
                            @elseif($doc->isExpiring())
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Expiring</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Valid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @can('manageDocuments', $employer)
                            <form action="{{ route('admin.employers.delete-document', $doc) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this document?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-sm mb-4">No additional documents uploaded.</p>
        @endif

        {{-- Upload Form --}}
        @can('manageDocuments', $employer)
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Upload New Document</h3>
            <form action="{{ route('admin.employers.upload-document', $employer) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Type --</option>
                            @foreach(\App\Models\EmployerDocument::documentTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Document Name</label>
                        <input type="text" name="document_name" placeholder="Optional custom name"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Document Number</label>
                        <input type="text" name="document_number" placeholder="License/registration number"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Issue Date</label>
                        <input type="date" name="issue_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">File <span class="text-red-500">*</span></label>
                        <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Max 10MB</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Notes</label>
                    <textarea name="document_notes" rows="2" placeholder="Optional notes..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </form>
        </div>
        @endcan
    </div>

    <!-- Linked Candidates Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-users"></i> Linked Candidates
            </h2>
            @if($employer->candidates->count() > 0)
                <a href="{{ route('admin.employers.candidates', $employer) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>

        @if($employer->candidates->count() > 0)
            <div class="mb-4">
                <p class="text-gray-700">
                    <strong>{{ $employer->currentCandidates()->count() }}</strong> current candidate(s),
                    <strong>{{ $employer->candidates->count() }}</strong> total linked
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignment Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($employer->candidates as $candidate)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $candidate->candidate_id ?? $candidate->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $candidate->full_name ?? $candidate->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $candidate->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($candidate->pivot->employment_type)
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                                        {{ ucfirst($candidate->pivot->employment_type) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @php $pivotStatus = $candidate->pivot->status ?? 'pending'; @endphp
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $pivotStatus === 'active' ? 'bg-green-100 text-green-800' :
                                       ($pivotStatus === 'completed' ? 'bg-gray-100 text-gray-800' :
                                       ($pivotStatus === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                    {{ ucfirst($pivotStatus) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $candidate->pivot->assigned_at ? \Carbon\Carbon::parse($candidate->pivot->assigned_at)->format('M d, Y') : ($candidate->pivot->assignment_date ? \Carbon\Carbon::parse($candidate->pivot->assignment_date)->format('M d, Y') : 'N/A') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('candidates.show', $candidate) }}" class="text-blue-600 hover:text-blue-900">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-md p-6 text-center">
                <p class="text-gray-600">No candidates currently linked to this employer</p>
            </div>
        @endif

        {{-- Assign Candidate Form --}}
        @can('assignCandidate', $employer)
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-md p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Assign Candidate</h3>
            <form action="{{ route('admin.employers.assign-candidate', $employer) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Candidate ID <span class="text-red-500">*</span></label>
                        <input type="number" name="candidate_id" required placeholder="Enter candidate ID"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Employment Type <span class="text-red-500">*</span></label>
                        <select name="employment_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(\App\Enums\EmploymentType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Custom Base Salary</label>
                        <input type="number" name="custom_base_salary" step="0.01" min="0" placeholder="Leave empty for default"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                    <i class="fas fa-user-plus"></i> Assign Candidate
                </button>
            </form>
        </div>
        @endcan
    </div>

    <!-- Notes Section -->
    @if($employer->notes)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Notes</h2>
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <p class="text-gray-800 whitespace-pre-line">{{ $employer->notes }}</p>
        </div>
    </div>
    @endif

    <!-- Record Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Record Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Created</label>
                <p class="text-gray-800">{{ $employer->created_at->format('M d, Y h:i A') }}</p>
                <p class="text-sm text-gray-500">{{ $employer->created_at->diffForHumans() }}</p>
            </div>
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Last Updated</label>
                <p class="text-gray-800">{{ $employer->updated_at->format('M d, Y h:i A') }}</p>
                <p class="text-sm text-gray-500">{{ $employer->updated_at->diffForHumans() }}</p>
            </div>
            @if($employer->creator)
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Created By</label>
                <p class="text-gray-800">{{ $employer->creator->name }}</p>
            </div>
            @endif
            @if($employer->verified && $employer->verifiedByUser)
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Verified By</label>
                <p class="text-gray-800">{{ $employer->verifiedByUser->name }}</p>
                <p class="text-sm text-gray-500">{{ $employer->verified_at?->format('M d, Y h:i A') }}</p>
            </div>
            @endif
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Employer ID</label>
                <p class="text-gray-800 font-mono">{{ $employer->id }}</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
        <div class="flex gap-3">
            @can('delete', $employer)
            <form action="{{ route('admin.employers.destroy', $employer) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this employer? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    <i class="fas fa-trash"></i> Delete Employer
                </button>
            </form>
            @endcan
        </div>
        <div class="flex gap-3">
            @can('update', $employer)
            <a href="{{ route('admin.employers.edit', $employer) }}"
               class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <i class="fas fa-edit"></i> Edit Employer
            </a>
            @endcan
            <a href="{{ route('admin.employers.index') }}"
               class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to List
            </a>
        </div>
    </div>
</div>
@endsection
