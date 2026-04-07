{{-- Employer Information Tab --}}
@php
    $currentEmployer = $candidate->employers->first(fn($e) => $e->pivot->is_current && $e->pivot->status === 'active');
    $allEmployers = $candidate->employers;
@endphp

<div class="space-y-6">

    {{-- No employer linked --}}
    @if($allEmployers->isEmpty())
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <i class="fas fa-building text-4xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Employer Linked</h3>
        <p class="text-gray-500 mb-4">This candidate has not been assigned to an employer yet.</p>
        @can('update', $candidate)
        <a href="{{ route('admin.employers.index') }}"
           class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-search mr-2"></i>Browse Employers
        </a>
        @endcan
    </div>
    @else

    {{-- Current Employer --}}
    @if($currentEmployer)
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-xl font-bold text-gray-900">{{ $currentEmployer->visa_issuing_company }}</h3>
                    @if($currentEmployer->verified)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-check-circle mr-1"></i>Verified
                        </span>
                    @endif
                    @if($currentEmployer->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                    @endif
                    @if($currentEmployer->company_size)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $currentEmployer->company_size->label() }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    {{ collect([$currentEmployer->city, $currentEmployer->country?->name])->filter()->implode(', ') ?: 'Location not specified' }}
                </p>
                @if($currentEmployer->sector)
                    <p class="text-sm text-gray-500 mt-0.5">
                        <i class="fas fa-industry mr-1"></i>{{ $currentEmployer->sector }}
                        @if($currentEmployer->tradeRelation)
                            &bull; {{ $currentEmployer->tradeRelation->name }}
                        @endif
                    </p>
                @endif
            </div>
            @can('view', App\Models\Employer::class)
            <a href="{{ route('admin.employers.show', $currentEmployer) }}"
               class="text-sm bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg transition">
                <i class="fas fa-external-link-alt mr-1"></i>Full Profile
            </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">

            {{-- Permission Details --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 border-b pb-2">
                    <i class="fas fa-file-contract mr-1 text-indigo-500"></i>Permission Details
                </h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Permission Number</dt>
                        <dd class="font-medium text-gray-900">{{ $currentEmployer->permission_number ?? '—' }}</dd>
                    </div>
                    @if($currentEmployer->permission_issue_date)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Issue Date</dt>
                        <dd class="font-medium text-gray-900">{{ $currentEmployer->permission_issue_date->format('d M, Y') }}</dd>
                    </div>
                    @endif
                    @if($currentEmployer->permission_expiry_date)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Expiry Date</dt>
                        <dd class="font-medium {{ $currentEmployer->permission_expired ? 'text-red-600' : ($currentEmployer->permission_expiring ? 'text-yellow-600' : 'text-gray-900') }}">
                            {{ $currentEmployer->permission_expiry_date->format('d M, Y') }}
                            @if($currentEmployer->permission_expired)
                                <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded">Expired</span>
                            @elseif($currentEmployer->permission_expiring)
                                <span class="ml-1 text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded">Expiring Soon</span>
                            @endif
                        </dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Visa Company</dt>
                        <dd class="font-medium text-gray-900">{{ $currentEmployer->visa_issuing_company ?? '—' }}</dd>
                    </div>
                    @if($currentEmployer->visa_company_license)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Visa License</dt>
                        <dd class="font-medium text-gray-900">{{ $currentEmployer->visa_company_license }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Employment Package --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 border-b pb-2">
                    <i class="fas fa-money-bill-wave mr-1 text-green-500"></i>Employment Package
                </h4>
                @php
                    $pivotPackage = $currentEmployer->pivot->custom_package
                        ? \App\ValueObjects\EmploymentPackage::fromArray(json_decode($currentEmployer->pivot->custom_package, true))
                        : null;
                    $package = $pivotPackage ?? $currentEmployer->default_package_object;
                @endphp
                @if($package->baseSalary > 0 || $currentEmployer->basic_salary > 0)
                <dl class="space-y-2 text-sm">
                    @if($package->baseSalary > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Base Salary</dt>
                        <dd class="font-semibold text-gray-900">{{ $package->currency }} {{ number_format($package->baseSalary, 2) }}</dd>
                    </div>
                    @if($package->housingAllowance > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Housing</dt>
                        <dd class="font-medium text-gray-900">{{ $package->currency }} {{ number_format($package->housingAllowance, 2) }}</dd>
                    </div>
                    @endif
                    @if($package->foodAllowance > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Food</dt>
                        <dd class="font-medium text-gray-900">{{ $package->currency }} {{ number_format($package->foodAllowance, 2) }}</dd>
                    </div>
                    @endif
                    @if($package->transportAllowance > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Transport</dt>
                        <dd class="font-medium text-gray-900">{{ $package->currency }} {{ number_format($package->transportAllowance, 2) }}</dd>
                    </div>
                    @endif
                    @if($package->otherAllowance > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Other</dt>
                        <dd class="font-medium text-gray-900">{{ $package->currency }} {{ number_format($package->otherAllowance, 2) }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <dt class="font-semibold text-gray-700">Total Package</dt>
                        <dd class="font-bold text-green-700">{{ $package->getFormattedTotal() }}</dd>
                    </div>
                    @else
                    {{-- Legacy salary fields --}}
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Basic Salary</dt>
                        <dd class="font-semibold text-gray-900">{{ $currentEmployer->salary_currency }} {{ number_format($currentEmployer->basic_salary, 2) }}</dd>
                    </div>
                    @endif
                </dl>

                {{-- Benefits --}}
                @if($currentEmployer->food_by_company || $currentEmployer->transport_by_company || $currentEmployer->accommodation_by_company)
                <div class="flex flex-wrap gap-1 mt-3">
                    @if($currentEmployer->food_by_company)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-emerald-100 text-emerald-700">
                            <i class="fas fa-utensils mr-1"></i>Food Provided
                        </span>
                    @endif
                    @if($currentEmployer->transport_by_company)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-sky-100 text-sky-700">
                            <i class="fas fa-bus mr-1"></i>Transport Provided
                        </span>
                    @endif
                    @if($currentEmployer->accommodation_by_company)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-violet-100 text-violet-700">
                            <i class="fas fa-home mr-1"></i>Accommodation Provided
                        </span>
                    @endif
                </div>
                @endif
                @else
                <p class="text-sm text-gray-400 italic">No package details recorded.</p>
                @endif
            </div>
        </div>

        {{-- Assignment Details --}}
        @if($currentEmployer->pivot->employment_type || $currentEmployer->pivot->assignment_date)
        <div class="mt-4 pt-4 border-t grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            @if($currentEmployer->pivot->employment_type)
            <div>
                <p class="text-gray-500">Employment Type</p>
                @php
                    $empType = \App\Enums\EmploymentType::tryFrom($currentEmployer->pivot->employment_type);
                @endphp
                <p class="font-medium text-gray-900">{{ $empType?->label() ?? ucfirst($currentEmployer->pivot->employment_type) }}</p>
            </div>
            @endif
            @if($currentEmployer->pivot->assignment_date)
            <div>
                <p class="text-gray-500">Assignment Date</p>
                <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($currentEmployer->pivot->assignment_date)->format('d M, Y') }}</p>
            </div>
            @endif
            <div>
                <p class="text-gray-500">Assignment Status</p>
                @php $pivotStatus = $currentEmployer->pivot->status; @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    {{ $pivotStatus === 'active' ? 'bg-green-100 text-green-800' :
                       ($pivotStatus === 'completed' ? 'bg-blue-100 text-blue-800' :
                       ($pivotStatus === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                    {{ ucfirst($pivotStatus ?? 'pending') }}
                </span>
            </div>
        </div>
        @endif

        {{-- Other Conditions / Notes --}}
        @if($currentEmployer->other_conditions)
        <div class="mt-4 pt-4 border-t">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Other Conditions</p>
            <p class="text-sm text-gray-700">{{ $currentEmployer->other_conditions }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- All Employers History --}}
    @if($allEmployers->count() > 1 || ($allEmployers->count() === 1 && !$currentEmployer))
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-history mr-2 text-gray-400"></i>Employer History
            <span class="ml-2 text-sm font-normal text-gray-500">({{ $allEmployers->count() }} total)</span>
        </h3>
        <div class="space-y-3">
            @foreach($allEmployers as $emp)
            @php
                $isCurrent = $emp->pivot->is_current && $emp->pivot->status === 'active';
            @endphp
            <div class="flex items-center justify-between p-3 rounded-lg {{ $isCurrent ? 'bg-indigo-50 border border-indigo-200' : 'bg-gray-50' }}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $emp->visa_issuing_company }}</p>
                        @if($isCurrent)
                            <span class="flex-shrink-0 text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">Current</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ collect([$emp->city, $emp->country?->name])->filter()->implode(', ') }}
                        @if($emp->pivot->assignment_date)
                            &bull; Assigned {{ \Carbon\Carbon::parse($emp->pivot->assignment_date)->format('d M, Y') }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 ml-3">
                    @php $st = $emp->pivot->status; @endphp
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $st === 'active' ? 'bg-green-100 text-green-700' :
                           ($st === 'completed' ? 'bg-blue-100 text-blue-700' :
                           ($st === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')) }}">
                        {{ ucfirst($st ?? 'pending') }}
                    </span>
                    @can('view', App\Models\Employer::class)
                    <a href="{{ route('admin.employers.show', $emp) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    @endcan
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif {{-- end: not empty --}}
</div>
