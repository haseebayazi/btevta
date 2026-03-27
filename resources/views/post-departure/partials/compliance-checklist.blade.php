<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clipboard-check"></i> 90-Day Compliance Checklist</h6>
        @if($detail->compliance_verified)
            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified on {{ $detail->compliance_verified_date?->format('d M Y') }}</span>
        @endif
    </div>
    <div class="card-body">
        @php
            $completedCount = collect($checklist)->filter(fn($item) => $item['complete'])->count();
            $totalCount = count($checklist);
            $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
        @endphp

        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span>Progress: {{ $completedCount }}/{{ $totalCount }} items</span>
                <span>{{ $percentage }}%</span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-{{ $percentage === 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger') }}"
                     role="progressbar" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        <div class="row">
            @foreach($checklist as $key => $item)
            <div class="col-md-4 mb-2">
                <div class="d-flex align-items-center">
                    @if($item['complete'])
                        <i class="fas fa-check-circle text-success mr-2"></i>
                    @else
                        <i class="fas fa-times-circle text-danger mr-2"></i>
                    @endif
                    <span>{{ $item['label'] }}</span>
                    @if(isset($item['expiring']) && $item['expiring'])
                        <span class="badge badge-warning ml-1">Expiring</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if(!$detail->compliance_verified && $percentage === 100)
        <hr>
        <form method="POST" action="{{ route('post-departure.verify-compliance', $detail) }}">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Verify 90-Day Compliance
            </button>
        </form>
        @endif
    </div>
</div>
