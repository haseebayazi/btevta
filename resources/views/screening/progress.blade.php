@extends('layouts.app')

@section('title', 'Screening Progress')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Screening Progress</h1>
            <p class="text-gray-600 mt-1">{{ $candidate->name }} ({{ $candidate->btevta_id }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-info">
                <i class="fas fa-user mr-2"></i>View Candidate
            </a>
            <a href="{{ route('screening.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Progress -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Progress Overview -->
            <div class="card">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-clipboard-check mr-2 text-blue-500"></i>Screening Overview
                    </h2>
                    @php
                        $allPassed = isset($progress['all_passed']) && $progress['all_passed'];
                        $anyFailed = collect($progress['screenings'] ?? [])->where('status', 'failed')->isNotEmpty();
                    @endphp
                    @if($allPassed)
                        <span class="badge badge-success text-sm px-3 py-1">All Screenings Passed</span>
                    @elseif($anyFailed)
                        <span class="badge badge-danger text-sm px-3 py-1">Screening Failed</span>
                    @else
                        <span class="badge badge-warning text-sm px-3 py-1">In Progress</span>
                    @endif
                </div>

                <!-- Visual Progress -->
                <div class="flex items-center justify-between mb-8">
                    @php
                        $screeningTypes = ['desk' => 'Desk', 'call' => 'Call', 'physical' => 'Physical'];
                        $screenings = $progress['screenings'] ?? [];
                    @endphp

                    @foreach($screeningTypes as $type => $label)
                        @php
                            $screening = $screenings[$type] ?? null;
                            $status = $screening['status'] ?? 'pending';
                            $isLast = $loop->last;
                        @endphp
                        <div class="flex-1 {{ !$isLast ? 'relative' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center
                                    {{ $status == 'passed' ? 'bg-green-500' : ($status == 'failed' ? 'bg-red-500' : ($status == 'in_progress' ? 'bg-yellow-500' : 'bg-gray-300')) }}">
                                    @if($status == 'passed')
                                        <i class="fas fa-check text-white text-2xl"></i>
                                    @elseif($status == 'failed')
                                        <i class="fas fa-times text-white text-2xl"></i>
                                    @elseif($status == 'in_progress')
                                        <i class="fas fa-spinner fa-spin text-white text-2xl"></i>
                                    @else
                                        <i class="fas fa-circle text-white text-xl"></i>
                                    @endif
                                </div>
                                <p class="mt-2 font-medium text-gray-900">{{ $label }}</p>
                                <p class="text-sm {{ $status == 'passed' ? 'text-green-600' : ($status == 'failed' ? 'text-red-600' : 'text-gray-500') }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </p>
                            </div>

                            @if(!$isLast)
                                <div class="absolute top-8 left-1/2 w-full h-1 {{ $status == 'passed' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Detailed Status -->
                <div class="space-y-4">
                    @foreach($screeningTypes as $type => $label)
                        @php
                            $screening = $screenings[$type] ?? null;
                            $status = $screening['status'] ?? 'pending';
                        @endphp
                        <div class="border rounded-lg p-4 {{ $status == 'passed' ? 'border-green-200 bg-green-50' : ($status == 'failed' ? 'border-red-200 bg-red-50' : ($status == 'in_progress' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50')) }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                        {{ $status == 'passed' ? 'bg-green-500' : ($status == 'failed' ? 'bg-red-500' : ($status == 'in_progress' ? 'bg-yellow-500' : 'bg-gray-400')) }}">
                                        @if($type == 'desk')
                                            <i class="fas fa-desktop text-white"></i>
                                        @elseif($type == 'call')
                                            <i class="fas fa-phone text-white"></i>
                                        @else
                                            <i class="fas fa-user-check text-white"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $label }} Screening</h3>
                                        <p class="text-sm text-gray-600">
                                            @if($type == 'desk')
                                                Document verification and eligibility check
                                            @elseif($type == 'call')
                                                Phone interview and availability confirmation
                                            @else
                                                In-person verification and assessment
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-{{ $status == 'passed' ? 'success' : ($status == 'failed' ? 'danger' : ($status == 'in_progress' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </span>
                                    @if(isset($screening['screened_at']))
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($screening['screened_at'])->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if(isset($screening['remarks']) && $screening['remarks'])
                                <div class="mt-3 pt-3 border-t {{ $status == 'passed' ? 'border-green-200' : ($status == 'failed' ? 'border-red-200' : 'border-gray-200') }}">
                                    <p class="text-sm text-gray-600">
                                        <strong>Remarks:</strong> {{ $screening['remarks'] }}
                                    </p>
                                </div>
                            @endif

                            @if($type == 'call' && isset($screening['call_count']))
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-phone-alt mr-1"></i>
                                        Call attempts: {{ $screening['call_count'] }}/3
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            @if(!$allPassed && !$anyFailed)
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-tools mr-2 text-gray-500"></i>Record Screening Outcome
                </h2>
                <form action="{{ route('screening.outcome', $candidate) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Screening Type</label>
                            <select name="screening_type" class="form-select w-full">
                                @foreach($screeningTypes as $type => $label)
                                    @php $typeStatus = $screenings[$type]['status'] ?? 'pending'; @endphp
                                    @if($typeStatus != 'passed')
                                        <option value="{{ $type }}">{{ $label }} Screening</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Outcome</label>
                            <select name="status" class="form-select w-full">
                                <option value="passed">Passed</option>
                                <option value="failed">Failed</option>
                                <option value="deferred">Deferred</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" rows="2" class="form-input w-full" placeholder="Add any notes..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Record Outcome
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Candidate Card -->
            <div class="card text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-blue-600 text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold">{{ $candidate->name }}</h3>
                <p class="text-gray-600 font-mono">{{ $candidate->btevta_id }}</p>
                <div class="mt-3">
                    <span class="badge badge-{{ $candidate->status == 'registered' ? 'success' : ($candidate->status == 'rejected' ? 'danger' : 'info') }}">
                        {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                    </span>
                </div>
            </div>

            <!-- Progress Summary -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-chart-pie mr-2 text-purple-500"></i>Summary
                </h2>
                @php
                    $passed = collect($screenings)->where('status', 'passed')->count();
                    $failed = collect($screenings)->where('status', 'failed')->count();
                    $pending = 3 - $passed - $failed;
                @endphp
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Passed</span>
                        <span class="font-medium text-green-600">{{ $passed }}/3</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Failed</span>
                        <span class="font-medium text-red-600">{{ $failed }}/3</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Pending</span>
                        <span class="font-medium text-yellow-600">{{ $pending }}/3</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t">
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        @php $progressPercent = ($passed / 3) * 100; @endphp
                        <div class="bg-green-500 h-3 rounded-full" style="width: {{ $progressPercent }}%"></div>
                    </div>
                    <p class="text-sm text-gray-500 text-center mt-2">{{ number_format($progressPercent, 0) }}% Complete</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-bolt mr-2 text-yellow-500"></i>Quick Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-info w-full justify-center">
                        <i class="fas fa-user mr-2"></i>View Candidate
                    </a>
                    @if($allPassed)
                        <a href="{{ route('registration.show', $candidate) }}" class="btn btn-success w-full justify-center">
                            <i class="fas fa-clipboard-list mr-2"></i>Start Registration
                        </a>
                    @endif
                </div>
            </div>

            <!-- Info Box -->
            <div class="card bg-blue-50 border border-blue-200">
                <h2 class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Screening Process
                </h2>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><i class="fas fa-check mr-1"></i>All 3 screenings must pass</li>
                    <li><i class="fas fa-check mr-1"></i>Any failure rejects candidate</li>
                    <li><i class="fas fa-check mr-1"></i>Pass triggers registration</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
