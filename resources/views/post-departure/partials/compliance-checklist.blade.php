<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-clipboard-check mr-2 text-blue-500"></i>90-Day Compliance Checklist</h3>
        @if($detail->compliance_verified)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <i class="fas fa-check-circle mr-1"></i>Verified {{ $detail->compliance_verified_date?->format('d M Y') }}
        </span>
        @endif
    </div>
    <div class="px-5 py-4">
        @php
            $completedCount = collect($checklist)->filter(fn($item) => $item['complete'])->count();
            $totalCount = count($checklist);
            $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
            $barColor = $percentage === 100 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-500');
        @endphp

        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>Progress: {{ $completedCount }}/{{ $totalCount }} items</span>
                <span>{{ $percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($checklist as $key => $item)
            <div class="flex items-center gap-2">
                @if($item['complete'])
                <i class="fas fa-check-circle text-green-500 flex-shrink-0"></i>
                @else
                <i class="fas fa-times-circle text-red-400 flex-shrink-0"></i>
                @endif
                <span class="text-sm text-gray-700">{{ $item['label'] }}</span>
                @if(isset($item['expiring']) && $item['expiring'])
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Expiring</span>
                @endif
            </div>
            @endforeach
        </div>

        @if(!$detail->compliance_verified && $percentage === 100)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <form method="POST" action="{{ route('post-departure.verify-compliance', $detail) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Verify 90-Day Compliance
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
