<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-briefcase mr-2 text-blue-500"></i>Employment History</h3>
    </div>
    <div class="px-5 py-4">
        @if($employmentHistory->isNotEmpty())
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salary (Total)</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($employmentHistory as $employment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $employment->sequence_label }}</td>
                        <td class="px-3 py-3 text-sm font-medium text-gray-900">{{ $employment->company_name }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $employment->position_title ?? 'N/A' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ number_format($employment->total_package, 2) }} {{ $employment->salary_currency ?? 'SAR' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $employment->commencement_date?->format('d M Y') ?? 'N/A' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $employment->end_date?->format('d M Y') ?? 'Ongoing' }}</td>
                        <td class="px-3 py-3">
                            @php
                                $empColor = match($employment->status?->value) {
                                    'current' => 'bg-green-100 text-green-800',
                                    'previous' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $empColor }}">
                                {{ $employment->status?->label() ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $employment->employment_duration ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500 mb-5">No employment records yet.</p>
        @endif

        @if($employmentHistory->isEmpty())
        <div class="border-t border-gray-200 pt-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Record Initial Employment</h4>
            <form method="POST" action="{{ route('post-departure.add-employment', $detail) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name"
                               value="{{ old('company_name') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('company_name') border-red-500 @enderror"
                               required>
                        @error('company_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position Title</label>
                        <input type="text" name="position_title"
                               value="{{ old('position_title') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Location</label>
                        <input type="text" name="work_location"
                               value="{{ old('work_location') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base Salary <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="base_salary"
                               value="{{ old('base_salary') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('base_salary') border-red-500 @enderror"
                               required>
                        @error('base_salary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency <span class="text-red-500">*</span></label>
                        <input type="text" name="currency"
                               value="{{ old('currency', 'SAR') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Housing</label>
                        <input type="number" step="0.01" name="housing_allowance"
                               value="{{ old('housing_allowance') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Food</label>
                        <input type="number" step="0.01" name="food_allowance"
                               value="{{ old('food_allowance') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transport</label>
                        <input type="number" step="0.01" name="transport_allowance"
                               value="{{ old('transport_allowance') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commencement <span class="text-red-500">*</span></label>
                        <input type="date" name="commencement_date"
                               value="{{ old('commencement_date') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('commencement_date') border-red-500 @enderror"
                               required>
                        @error('commencement_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employment Contract (PDF)</label>
                        <input type="file" name="contract"
                               accept=".pdf"
                               class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-1.5 @error('contract') border-red-500 @enderror">
                        @error('contract')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Record Employment
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
