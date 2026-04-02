<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-exchange-alt mr-2 text-blue-500"></i>Company Switches</h3>
    </div>
    <div class="px-5 py-4">
        @if($switches->isNotEmpty())
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Switch #</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">From Company</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">To Company</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($switches as $switch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $switch->switch_number }}</td>
                        <td class="px-3 py-3 text-sm text-gray-900">{{ $switch->fromEmployment?->company_name ?? 'N/A' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-900">{{ $switch->toEmployment?->company_name ?? 'N/A' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ Str::limit($switch->reason, 50) }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ $switch->switch_date->format('d M Y') }}</td>
                        <td class="px-3 py-3">
                            @php
                                $switchColor = match($switch->status->value) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $switchColor }}">
                                {{ $switch->status->label() }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            @if($switch->status->value === 'pending')
                                @can('approve', $switch)
                                <form method="POST" action="{{ route('post-departure.approve-switch', $switch) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700"
                                            title="Approve">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                @endcan
                            @elseif($switch->status->value === 'approved')
                                @can('complete', $switch)
                                <form method="POST" action="{{ route('post-departure.complete-switch', $switch) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
                                            title="Complete">
                                        <i class="fas fa-flag-checkered mr-1"></i>Complete
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @php
            $currentEmployment = $detail->currentEmployment;
            $completedSwitches = $switches->filter(fn($s) => in_array($s->status->value, ['approved', 'completed']))->count();
        @endphp

        @if($currentEmployment && $completedSwitches < 2)
        <div class="border-t border-gray-200 pt-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Initiate Company Switch</h4>
            <form method="POST" action="{{ route('post-departure.initiate-switch', $detail) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name"
                               value="{{ old('company_name') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('company_name') border-red-500 @enderror"
                               required>
                        @error('company_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Switch <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="2"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('reason') border-red-500 @enderror"
                                  required>{{ old('reason') }}</textarea>
                        @error('reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Base Salary <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="base_salary"
                               value="{{ old('base_salary') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('base_salary') border-red-500 @enderror"
                               required>
                        @error('base_salary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commencement Date <span class="text-red-500">*</span></label>
                        <input type="date" name="commencement_date"
                               value="{{ old('commencement_date') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('commencement_date') border-red-500 @enderror"
                               required>
                        @error('commencement_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Release Letter (PDF) <span class="text-red-500">*</span></label>
                        <input type="file" name="release_letter"
                               accept=".pdf"
                               class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-1.5 @error('release_letter') border-red-500 @enderror"
                               required>
                        @error('release_letter')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Contract (PDF)</label>
                        <input type="file" name="new_contract"
                               accept=".pdf"
                               class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-1.5 @error('new_contract') border-red-500 @enderror">
                        @error('new_contract')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                        <i class="fas fa-exchange-alt mr-2"></i>Initiate Switch
                    </button>
                </div>
            </form>
        </div>
        @elseif($completedSwitches >= 2)
        <div class="flex items-center gap-2 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <i class="fas fa-info-circle flex-shrink-0"></i>
            Maximum of 2 company switches has been reached.
        </div>
        @elseif(!$currentEmployment)
        <div class="flex items-center gap-2 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <i class="fas fa-info-circle flex-shrink-0"></i>
            Record initial employment before initiating a company switch.
        </div>
        @endif
    </div>
</div>
