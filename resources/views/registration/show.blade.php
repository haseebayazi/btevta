@extends('layouts.app')
@section('title', 'Registration Details - ' . $candidate->name)
@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-start md:justify-between">
        <div>
            <nav class="flex items-center text-sm text-gray-500 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-gray-400"></i>
                <a href="{{ route('registration.index') }}" class="hover:text-blue-600">Registration</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-gray-400"></i>
                <span class="text-gray-700 font-medium">{{ $candidate->btevta_id }}</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Registration Management</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $candidate->name }} ({{ $candidate->btevta_id }})</p>
        </div>
        <div class="flex space-x-2 mt-4 md:mt-0">
            <a href="{{ route('registration.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
            <a href="{{ route('candidates.show', $candidate->id) }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white text-sm font-medium rounded-lg hover:bg-cyan-700 transition">
                <i class="fas fa-user mr-2"></i> View Profile
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-green-500"></i>{{ session('success') }}
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-400 hover:text-green-600 text-lg">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>{{ session('error') }}
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-lg">&times;</button>
    </div>
    @endif

    {{-- Module 3: Allocation CTA for screened candidates --}}
    @if($candidate->status === \App\Enums\CandidateStatus::SCREENED->value)
    <div class="bg-green-50 border border-green-200 rounded-xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h5 class="text-lg font-semibold text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>Candidate Ready for Registration
                </h5>
                <p class="text-green-700 text-sm mt-1">This candidate has been screened. Proceed to allocation to complete registration with Campus, Program, Course, and NOK details.</p>
            </div>
            <a href="{{ route('registration.allocation', $candidate->id) }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition whitespace-nowrap">
                <i class="fas fa-clipboard-list mr-2"></i>Proceed to Allocation
            </a>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Candidate Basic Info --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="bg-blue-600 text-white px-6 py-4">
                    <h5 class="font-semibold"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">TheLeap ID</span>
                            <p class="font-mono font-bold text-gray-900 mt-1">{{ $candidate->btevta_id }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">CNIC</span>
                            <p class="font-mono text-gray-900 mt-1">{{ $candidate->formatted_cnic ?? $candidate->cnic ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</span>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $candidate->status === \App\Enums\CandidateStatus::REGISTERED->value ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Campus</span>
                            <p class="text-gray-900 mt-1">{{ $candidate->campus?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Trade</span>
                            <p class="text-gray-900 mt-1">{{ $candidate->trade?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</span>
                            <p class="text-gray-900 mt-1">{{ $candidate->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pre-Departure Documents (Module 1) --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h5 class="font-semibold text-gray-900"><i class="fas fa-file-alt mr-2 text-gray-500"></i>Pre-Departure Documents</h5>
                    <p class="text-xs text-gray-500 mt-1">Documents uploaded during Module 1 (Pre-Departure)</p>
                </div>
                <div class="p-6">
                    @if($candidate->preDepartureDocuments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Document</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Category</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Uploaded</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($candidate->preDepartureDocuments as $doc)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5">
                                                <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">{{ $doc->documentChecklist?->name ?? 'Document' }}</span>
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600 text-xs">
                                                @if($doc->documentChecklist?->is_mandatory)
                                                    <span class="text-red-600 font-semibold">Mandatory</span>
                                                @else
                                                    <span class="text-gray-500">Optional</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $doc->uploaded_at ? $doc->uploaded_at->format('d M Y') : $doc->created_at->format('d M Y') }}</td>
                                            <td class="px-4 py-2.5">
                                                @if($doc->isVerified())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Verified
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i>Uploaded
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 text-sm">
                            <i class="fas fa-exclamation-triangle mr-2"></i>No pre-departure documents found for this candidate.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Next of Kin Section --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h5 class="font-semibold text-gray-900"><i class="fas fa-users mr-2 text-gray-500"></i>Next of Kin Information</h5>
                </div>
                <div class="p-6">
                    <form action="{{ route('registration.next-of-kin', $candidate->id) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                       value="{{ old('name', $candidate->nextOfKin->name ?? '') }}" required>
                                @error('name')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Relationship <span class="text-red-500">*</span></label>
                                <select name="relationship" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('relationship') border-red-500 @enderror" required>
                                    <option value="">Select</option>
                                    @foreach(['Father', 'Mother', 'Spouse', 'Brother', 'Sister', 'Son', 'Daughter', 'Uncle', 'Aunt', 'Other'] as $rel)
                                        <option value="{{ $rel }}" {{ old('relationship', $candidate->nextOfKin->relationship ?? '') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                    @endforeach
                                </select>
                                @error('relationship')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700">CNIC <span class="text-red-500">*</span></label>
                                <input type="text" name="cnic" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cnic') border-red-500 @enderror"
                                       value="{{ old('cnic', $candidate->nextOfKin->cnic ?? '') }}"
                                       placeholder="1234567891234" maxlength="13" required>
                                @error('cnic')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Phone <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                                       value="{{ old('phone', $candidate->nextOfKin->phone ?? '') }}" required>
                                @error('phone')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Occupation</label>
                                <input type="text" name="occupation" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                       value="{{ old('occupation', $candidate->nextOfKin->occupation ?? '') }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold text-gray-700">Address <span class="text-red-500">*</span></label>
                                <textarea name="address" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror" rows="1" required>{{ old('address', $candidate->nextOfKin->address ?? '') }}</textarea>
                                @error('address')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="mt-4 flex items-center space-x-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-save mr-2"></i>Save Next of Kin
                            </button>
                            @if($candidate->nextOfKin)
                                <span class="inline-flex items-center px-2.5 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><i class="fas fa-check mr-1"></i> Saved</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Undertakings Section --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h5 class="font-semibold text-gray-900"><i class="fas fa-file-signature mr-2 text-gray-500"></i>Undertakings & Declarations</h5>
                </div>
                <div class="p-6">
                    {{-- Add Undertaking Form --}}
                    <form action="{{ route('registration.undertaking', $candidate->id) }}" method="POST" enctype="multipart/form-data" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        @csrf
                        <h6 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-plus mr-1"></i>Sign New Undertaking</h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Undertaking Type <span class="text-red-500">*</span></label>
                                <select name="undertaking_type" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('undertaking_type') border-red-500 @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="employment">Employment Terms</option>
                                    <option value="financial">Financial Obligations</option>
                                    <option value="behavior">Code of Conduct</option>
                                    <option value="other">Other Declaration</option>
                                </select>
                                @error('undertaking_type')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Witness Name</label>
                                <input type="text" name="witness_name" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" placeholder="Optional">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Witness CNIC</label>
                                <input type="text" name="witness_cnic" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" placeholder="Optional" maxlength="13">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                            <div class="md:col-span-2">
                                <label class="text-xs font-medium text-gray-600">Declaration Content <span class="text-red-500">*</span></label>
                                <textarea name="content" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('content') border-red-500 @enderror" rows="2" required placeholder="Enter the undertaking/declaration text..."></textarea>
                                @error('content')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Signature (Image)</label>
                                <input type="file" name="signature" class="w-full mt-1 text-sm" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-signature mr-2"></i>Record Undertaking
                        </button>
                    </form>

                    {{-- Undertakings List --}}
                    @if($candidate->undertakings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Type</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Content</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Witness</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Signed At</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($candidate->undertakings as $undertaking)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5">
                                                <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full font-medium">{{ ucfirst(str_replace('_', ' ', $undertaking->undertaking_type)) }}</span>
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600 text-xs max-w-xs truncate">{{ Str::limit($undertaking->content, 100) }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $undertaking->witness_name ?? '-' }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $undertaking->signed_at ? $undertaking->signed_at->format('d M Y H:i') : '-' }}</td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full font-semibold">
                                                    <i class="fas fa-check mr-1"></i> Signed
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-blue-700 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>No undertakings signed yet. At least one undertaking is required to complete registration.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- OEP Allocation --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="bg-cyan-600 text-white px-6 py-4">
                    <h5 class="font-semibold"><i class="fas fa-building mr-2"></i>OEP Allocation</h5>
                </div>
                <div class="p-6">
                    <form action="{{ route('candidates.update', $candidate->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="text-sm font-semibold text-gray-700">Overseas Employment Promoter</label>
                            <select name="oep_id" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('oep_id') border-red-500 @enderror">
                                <option value="">-- Not Assigned --</option>
                                @foreach(\App\Models\Oep::where('is_active', true)->get() as $oep)
                                    <option value="{{ $oep->id }}" {{ $candidate->oep_id == $oep->id ? 'selected' : '' }}>
                                        {{ $oep->name }} ({{ $oep->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('oep_id')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            <p class="text-xs text-gray-500 mt-1">Assign candidate to an OEP based on demand or trade specialization</p>
                        </div>
                        {{-- Include required hidden fields --}}
                        <input type="hidden" name="btevta_id" value="{{ $candidate->btevta_id }}">
                        <input type="hidden" name="name" value="{{ $candidate->name }}">
                        <input type="hidden" name="father_name" value="{{ $candidate->father_name }}">
                        <input type="hidden" name="cnic" value="{{ $candidate->cnic }}">
                        <input type="hidden" name="date_of_birth" value="{{ $candidate->date_of_birth?->format('Y-m-d') }}">
                        <input type="hidden" name="gender" value="{{ $candidate->gender }}">
                        <input type="hidden" name="phone" value="{{ $candidate->phone }}">
                        <input type="hidden" name="email" value="{{ $candidate->email }}">
                        <input type="hidden" name="address" value="{{ $candidate->address }}">
                        <input type="hidden" name="district" value="{{ $candidate->district }}">
                        <input type="hidden" name="trade_id" value="{{ $candidate->trade_id }}">
                        <button type="submit" class="w-full px-4 py-2 bg-cyan-600 text-white text-sm font-medium rounded-lg hover:bg-cyan-700 transition">
                            <i class="fas fa-save mr-2"></i>Update OEP Assignment
                        </button>
                    </form>
                    @if($candidate->oep)
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <span class="text-xs font-medium text-gray-500">Current OEP:</span>
                            <p class="text-blue-600 font-semibold">{{ $candidate->oep->name }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Registration Checklist --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h5 class="font-semibold text-gray-900"><i class="fas fa-tasks mr-2 text-gray-500"></i>Registration Checklist</h5>
                </div>
                <div class="p-6">
                    @php
                        $hasNextOfKin = $candidate->nextOfKin !== null;
                    @endphp

                    <ul class="space-y-3">
                        <li class="flex items-start text-sm">
                            <i class="fas {{ $allPreDepartureDocsUploaded ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-400' }} mr-3 mt-0.5 text-base"></i>
                            <span class="text-gray-700">Pre-Departure Documents (Module 1)</span>
                        </li>
                        <li class="flex items-start text-sm">
                            <i class="fas fa-check-circle text-green-500 mr-3 mt-0.5 text-base"></i>
                            <span class="text-gray-700">Screening Completed (Module 2)</span>
                        </li>
                        <li class="flex items-start text-sm">
                            <i class="fas {{ $hasNextOfKin ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-400' }} mr-3 mt-0.5 text-base"></i>
                            <span class="text-gray-700">Next of Kin Information {{ $hasNextOfKin ? '' : '(can be added during allocation)' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Registration Status --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border-2 {{ $candidate->status === \App\Enums\CandidateStatus::REGISTERED->value ? 'border-green-300' : 'border-gray-200' }}">
                <div class="bg-green-600 text-white px-6 py-4">
                    <h5 class="font-semibold"><i class="fas fa-check-circle mr-2"></i>Registration</h5>
                </div>
                <div class="p-6">
                    @if($candidate->status === \App\Enums\CandidateStatus::REGISTERED->value)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                            <p class="text-green-800 font-semibold">Registration Completed!</p>
                            <p class="text-green-600 text-xs mt-1">{{ $candidate->registration_date ? $candidate->registration_date->format('d M Y H:i') : $candidate->updated_at->format('d M Y H:i') }}</p>
                            @if($candidate->allocated_number)
                                <p class="text-green-700 text-sm mt-2 font-mono">Allocated #: {{ $candidate->allocated_number }}</p>
                            @endif
                        </div>
                    @elseif($candidate->status === \App\Enums\CandidateStatus::SCREENED->value)
                        <p class="text-gray-500 text-sm mb-3">Proceed to allocation to assign Campus, Program, Course, and complete registration.</p>
                        <a href="{{ route('registration.allocation', $candidate->id) }}" class="block w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition text-lg text-center">
                            <i class="fas fa-clipboard-list mr-2"></i>Proceed to Allocation
                        </a>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-2"></i>
                            <p class="text-yellow-800 font-semibold">Not Eligible</p>
                            <p class="text-yellow-600 text-xs mt-1">Candidate must be screened before registration.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
