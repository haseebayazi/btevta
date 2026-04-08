<div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
    <h4 class="font-semibold text-gray-700 text-sm mb-3">
        <i class="fas fa-tags text-orange-500 mr-1"></i>Add Categorized Evidence
    </h4>

    <form method="POST"
          action="{{ route('complaints.add-categorized-evidence', $complaint) }}"
          enctype="multipart/form-data">
        @csrf

        <div class="grid md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Evidence Category <span class="text-red-500">*</span>
                </label>
                <select name="evidence_category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                    <option value="">— Select category —</option>
                    @foreach(\App\Enums\ComplaintEvidenceCategory::cases() as $cat)
                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    File <span class="text-red-500">*</span>
                </label>
                <input type="file" name="file" required
                       class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white focus:ring-2 focus:ring-orange-400 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG, DOC, DOCX (max 10MB)</p>
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea name="description" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none"
                      placeholder="Describe what this evidence shows..."></textarea>
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_confidential" value="1"
                       class="w-4 h-4 text-orange-500 rounded">
                <span class="text-xs text-gray-600">
                    <i class="fas fa-lock text-gray-400 mr-1"></i>Mark as confidential
                </span>
            </label>
            <button type="submit"
                    class="px-4 py-2 bg-orange-500 text-white rounded text-xs hover:bg-orange-600 transition-colors">
                <i class="fas fa-upload mr-1"></i> Upload Evidence
            </button>
        </div>
    </form>
</div>
