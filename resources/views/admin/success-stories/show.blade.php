@extends('layouts.app')
@section('title', 'Success Story — ' . ($successStory->headline ?: Str::limit($successStory->written_note, 50)))

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">
    {{-- Breadcrumb --}}
    <div class="mb-4">
        <a href="{{ route('admin.success-stories.index') }}"
           class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1 w-fit">
            <i class="fas fa-arrow-left"></i> Back to Stories
        </a>
    </div>


    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Story Card --}}
            <div class="bg-white rounded-lg border shadow-sm p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        @if($successStory->story_type)
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-{{ $successStory->story_type->color() }}-100 text-{{ $successStory->story_type->color() }}-700 mb-2">
                            <i class="{{ $successStory->story_type->icon() }}"></i>
                            {{ $successStory->story_type->label() }}
                        </span>
                        @endif
                        <h1 class="text-xl font-bold text-gray-800 mt-1">
                            {{ $successStory->headline ?: 'Success Story #' . $successStory->id }}
                        </h1>
                    </div>
                    @if($successStory->is_featured)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700">
                        <i class="fas fa-star"></i> Featured
                    </span>
                    @endif
                </div>

                <div class="prose max-w-none text-gray-700 text-sm leading-relaxed">
                    {!! nl2br(e($successStory->written_note)) !!}
                </div>

                {{-- Metrics --}}
                <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($successStory->views_count) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Views</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $successStory->evidence->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Evidence Files</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600">
                            {{ $successStory->time_to_employment_days ? $successStory->time_to_employment_days . 'd' : '—' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Days to Employment</p>
                    </div>
                </div>
            </div>

            {{-- Employment Outcome --}}
            @if($successStory->employer_name || $successStory->position_achieved || $successStory->salary_achieved)
            <div class="bg-white rounded-lg border shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-briefcase text-indigo-500 mr-2"></i>Employment Outcome
                </h2>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    @if($successStory->employer_name)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Employer</p>
                        <p class="font-semibold text-gray-800">{{ $successStory->employer_name }}</p>
                    </div>
                    @endif
                    @if($successStory->position_achieved)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Position</p>
                        <p class="font-semibold text-gray-800">{{ $successStory->position_achieved }}</p>
                    </div>
                    @endif
                    @if($successStory->country)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Country</p>
                        <p class="font-semibold text-gray-800">{{ $successStory->country->name }}</p>
                    </div>
                    @endif
                    @if($successStory->salary_achieved)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Salary</p>
                        <p class="font-semibold text-green-600">
                            {{ number_format($successStory->salary_achieved, 2) }} {{ $successStory->salary_currency }}
                        </p>
                    </div>
                    @endif
                    @if($successStory->employment_start_date)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Start Date</p>
                        <p class="font-semibold text-gray-800">{{ $successStory->employment_start_date->format('d M Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Evidence Gallery --}}
            <div class="bg-white rounded-lg border shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-images text-purple-500 mr-2"></i>Evidence Gallery
                    </h2>
                    @can('update', $successStory)
                    <button onclick="document.getElementById('evidenceForm').classList.toggle('hidden')"
                            class="px-3 py-1 bg-purple-600 text-white rounded text-xs hover:bg-purple-700 transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Evidence
                    </button>
                    @endcan
                </div>

                {{-- Add Evidence Form --}}
                @can('update', $successStory)
                <div id="evidenceForm" class="hidden mb-6">
                    @include('admin.success-stories.partials.evidence-upload')
                </div>
                @endcan

                {{-- Evidence Items --}}
                @include('admin.success-stories.partials.evidence-gallery')
            </div>

            {{-- Approval / Rejection Notes --}}
            @if($successStory->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-red-700 mb-1">
                    <i class="fas fa-times-circle mr-2"></i>Rejection Reason
                </p>
                <p class="text-sm text-red-600">{{ $successStory->rejection_reason }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Candidate Info --}}
            <div class="bg-white rounded-lg border shadow-sm p-4">
                <h3 class="font-semibold text-gray-700 mb-3 text-sm">
                    <i class="fas fa-user-circle text-blue-500 mr-2"></i>Candidate
                </h3>
                <div class="space-y-2 text-sm">
                    <p class="font-semibold text-gray-800">{{ $successStory->candidate->name }}</p>
                    <p class="text-gray-500">{{ $successStory->candidate->btevta_id }}</p>
                    <p class="text-gray-500">{{ $successStory->candidate->cnic }}</p>
                    <a href="{{ route('candidates.show', $successStory->candidate) }}"
                       class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mt-1">
                        <i class="fas fa-external-link-alt"></i> View Profile
                    </a>
                </div>
            </div>

            {{-- Status & Workflow --}}
            <div class="bg-white rounded-lg border shadow-sm p-4">
                <h3 class="font-semibold text-gray-700 mb-3 text-sm">
                    <i class="fas fa-tasks text-green-500 mr-2"></i>Status & Workflow
                </h3>

                @if($successStory->status)
                <div class="mb-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $successStory->status->color() }}-100 text-{{ $successStory->status->color() }}-700">
                        {{ $successStory->status->label() }}
                    </span>
                </div>
                @endif

                @can('update', $successStory)
                <div class="space-y-2">
                    @if($successStory->status && $successStory->status->canSubmitForReview())
                    <form method="POST" action="{{ route('admin.success-stories.submit-review', $successStory) }}">
                        @csrf
                        <button type="submit"
                                class="w-full px-3 py-2 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600 transition-colors text-left">
                            <i class="fas fa-paper-plane mr-2"></i>Submit for Review
                        </button>
                    </form>
                    @endif

                    @if($successStory->status && $successStory->status->canApprove())
                    <form method="POST" action="{{ route('admin.success-stories.approve', $successStory) }}">
                        @csrf
                        <button type="submit"
                                class="w-full px-3 py-2 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition-colors text-left">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                    </form>
                    @endif

                    @if($successStory->status && $successStory->status->canPublish())
                    <form method="POST" action="{{ route('admin.success-stories.publish', $successStory) }}">
                        @csrf
                        <button type="submit"
                                class="w-full px-3 py-2 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition-colors text-left">
                            <i class="fas fa-globe mr-2"></i>Publish
                        </button>
                    </form>
                    @endif

                    @if($successStory->status && $successStory->status->canReject())
                    <button onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                            class="w-full px-3 py-2 bg-red-500 text-white rounded text-xs hover:bg-red-600 transition-colors text-left">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    @endif
                </div>
                @endcan

                {{-- Featured toggle --}}
                @can('update', $successStory)
                <form method="POST" action="{{ route('admin.success-stories.toggle-featured', $successStory) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full px-3 py-2 {{ $successStory->is_featured ? 'bg-yellow-100 text-yellow-700 border border-yellow-300' : 'bg-gray-100 text-gray-600 border border-gray-200' }} rounded text-xs hover:opacity-80 transition-colors text-left">
                        <i class="fas fa-star mr-2"></i>{{ $successStory->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}
                    </button>
                </form>
                @endcan
            </div>

            {{-- Story Meta --}}
            <div class="bg-white rounded-lg border shadow-sm p-4">
                <h3 class="font-semibold text-gray-700 mb-3 text-sm">
                    <i class="fas fa-info-circle text-gray-400 mr-2"></i>Details
                </h3>
                <div class="space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span>Recorded by</span>
                        <span class="font-medium">{{ $successStory->recorder->name ?? 'System' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Recorded at</span>
                        <span class="font-medium">{{ $successStory->recorded_at?->format('d M Y') ?? $successStory->created_at->format('d M Y') }}</span>
                    </div>
                    @if($successStory->approvedBy)
                    <div class="flex justify-between">
                        <span>Approved by</span>
                        <span class="font-medium">{{ $successStory->approvedBy->name }}</span>
                    </div>
                    @endif
                    @if($successStory->published_at)
                    <div class="flex justify-between">
                        <span>Published</span>
                        <span class="font-medium">{{ $successStory->published_at->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @can('update', $successStory)
            <div class="flex gap-2">
                <a href="{{ route('admin.success-stories.edit', $successStory) }}"
                   class="flex-1 text-center px-3 py-2 bg-indigo-600 text-white rounded text-xs hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @can('delete', $successStory)
                <form method="POST" action="{{ route('admin.success-stories.destroy', $successStory) }}"
                      onsubmit="return confirm('Delete this success story?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-2 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
            @endcan
        </div>
    </div>
</div>

{{-- Reject Modal --}}
@if($successStory->status && $successStory->status->canReject())
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Reject Story</h3>
        <form method="POST" action="{{ route('admin.success-stories.reject', $successStory) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="4" required
                          placeholder="Explain why this story is being rejected..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('rejectModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                    Reject Story
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
