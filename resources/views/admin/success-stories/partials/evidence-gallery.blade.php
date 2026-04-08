@if($successStory->evidence->isEmpty())
<div class="text-center py-8 text-gray-400">
    <i class="fas fa-images text-4xl mb-3 block opacity-30"></i>
    <p class="text-sm">No additional evidence uploaded yet.</p>
</div>
@else
<div class="grid sm:grid-cols-2 gap-4">
    @foreach($successStory->evidence as $item)
    <div class="border rounded-lg p-4 {{ $item->is_primary ? 'border-purple-300 bg-purple-50' : 'bg-gray-50' }} relative">
        @if($item->is_primary)
        <span class="absolute top-2 right-2 text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full">Primary</span>
        @endif

        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-white border flex items-center justify-center flex-shrink-0">
                <i class="{{ $item->evidence_type->icon() }} text-gray-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 text-sm truncate">{{ $item->title }}</p>
                <p class="text-xs text-gray-500">
                    {{ $item->evidence_type->label() }} &middot; {{ $item->formatted_file_size }}
                </p>
                @if($item->description)
                <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $item->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-1">by {{ $item->uploadedBy->name ?? 'System' }}</p>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-3">
            @can('update', $successStory)
            <form method="POST"
                  action="{{ route('admin.success-stories.delete-evidence', [$successStory, $item]) }}"
                  onsubmit="return confirm('Delete this evidence?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-2 py-1 text-red-600 hover:bg-red-100 rounded text-xs transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Legacy evidence file --}}
@if($successStory->evidence_path)
<div class="mt-4 pt-4 border-t">
    <p class="text-xs text-gray-500 mb-2 font-medium">Legacy Evidence</p>
    <div class="flex items-center justify-between bg-gray-50 border rounded p-3">
        <div class="flex items-center gap-2">
            <i class="fas fa-file text-gray-400"></i>
            <span class="text-sm text-gray-700">{{ $successStory->evidence_filename }}</span>
        </div>
        <a href="{{ route('admin.success-stories.download-evidence', $successStory) }}"
           class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-xs hover:bg-gray-300 transition-colors">
            <i class="fas fa-download mr-1"></i> Download
        </a>
    </div>
</div>
@endif
