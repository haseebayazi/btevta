@extends('layouts.app')
@section('title', 'Success Stories')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-star text-yellow-500 mr-2"></i>Success Stories
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Module 9A — Manage and publish candidate success stories</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('success-stories.public') }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 text-sm"
               target="_blank">
                <i class="fas fa-globe"></i> Public Gallery
            </a>
            @can('create', App\Models\SuccessStory::class)
            <a href="{{ route('admin.success-stories.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm">
                <i class="fas fa-plus"></i> New Story
            </a>
            @endcan
        </div>
    </div>


    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach(\App\Enums\StoryStatus::cases() as $s)
        <div class="bg-white rounded-lg border p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-{{ $s->color() }}-600">
                {{ $stories->where('status', $s)->count() }}
            </p>
            <p class="text-xs text-gray-500 mt-1">{{ $s->label() }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border shadow-sm p-4 mb-6">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search stories, candidates..."
                   class="col-span-2 md:col-span-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <select name="story_type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">All Types</option>
                @foreach($storyTypes as $val => $label)
                <option value="{{ $val }}" {{ request('story_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">All Statuses</option>
                @foreach($statuses as $val => $label)
                <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="is_featured" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">All</option>
                <option value="1" {{ request('is_featured') === '1' ? 'selected' : '' }}>Featured Only</option>
                <option value="0" {{ request('is_featured') === '0' ? 'selected' : '' }}>Not Featured</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.success-stories.index') }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Candidate</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Headline / Type</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Employment</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Recorded</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($stories as $story)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-blue-500 text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ $story->candidate->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $story->candidate->btevta_id ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800 truncate max-w-xs">
                            {{ $story->headline ?: Str::limit($story->written_note, 50) }}
                        </p>
                        @if($story->story_type)
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-{{ $story->story_type->color() }}-100 text-{{ $story->story_type->color() }}-700 mt-1">
                            <i class="{{ $story->story_type->icon() }}"></i>
                            {{ $story->story_type->label() }}
                        </span>
                        @endif
                        @if($story->is_featured)
                        <span class="ml-1 inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                            <i class="fas fa-star"></i> Featured
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($story->status)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $story->status->color() }}-100 text-{{ $story->status->color() }}-700">
                            {{ $story->status->label() }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($story->employer_name)
                        <p class="text-gray-800">{{ $story->employer_name }}</p>
                        @if($story->position_achieved)
                        <p class="text-xs text-gray-500">{{ $story->position_achieved }}</p>
                        @endif
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $story->recorded_at?->format('d M Y') ?? $story->created_at->format('d M Y') }}
                        <br>
                        <span class="text-gray-400">{{ $story->recorder->name ?? 'System' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.success-stories.show', $story) }}"
                               class="px-3 py-1 bg-blue-50 text-blue-700 rounded text-xs hover:bg-blue-100 transition-colors">
                                <i class="fas fa-eye"></i> View
                            </a>
                            @can('update', $story)
                            <a href="{{ route('admin.success-stories.edit', $story) }}"
                               class="px-3 py-1 bg-gray-50 text-gray-700 rounded text-xs hover:bg-gray-100 transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-star text-4xl mb-3 block opacity-30"></i>
                        No success stories found.
                        @can('create', App\Models\SuccessStory::class)
                        <a href="{{ route('admin.success-stories.create') }}" class="text-blue-600 hover:underline block mt-2">Add the first one</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($stories->hasPages())
        <div class="px-4 py-3 border-t">
            {{ $stories->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
