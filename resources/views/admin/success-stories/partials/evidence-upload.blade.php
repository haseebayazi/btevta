<form method="POST" action="{{ route('admin.success-stories.add-evidence', $successStory) }}"
      enctype="multipart/form-data"
      class="bg-purple-50 border border-purple-200 rounded-lg p-4">
    @csrf
    <h4 class="font-semibold text-gray-700 text-sm mb-3">
        <i class="fas fa-upload text-purple-500 mr-1"></i>Upload New Evidence
    </h4>

    <div class="grid md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Evidence Type <span class="text-red-500">*</span></label>
            <select name="evidence_type" required id="evidenceTypeSelect"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                <option value="">— Select type —</option>
                @foreach(\App\Enums\StoryEvidenceType::cases() as $type)
                <option value="{{ $type->value }}">
                    {{ $type->label() }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" required placeholder="Brief description"
                   class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">File <span class="text-red-500">*</span></label>
            <input type="file" name="file" required
                   class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Max 100MB for video/audio; 10MB for others</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea name="description" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                      placeholder="Optional description..."></textarea>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_primary" value="1"
                   class="w-4 h-4 text-purple-600 rounded">
            <span class="text-xs text-gray-600">Set as primary evidence (shown in gallery)</span>
        </label>
        <button type="submit"
                class="px-4 py-2 bg-purple-600 text-white rounded text-xs hover:bg-purple-700 transition-colors">
            <i class="fas fa-upload mr-1"></i> Upload
        </button>
    </div>
</form>
