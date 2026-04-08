@extends('layouts.app')
@section('title', 'Complaint Templates')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-clipboard-list text-indigo-500 mr-2"></i>Complaint Templates
            </h1>
            <p class="text-gray-500 text-sm mt-1">Pre-configured templates to speed up complaint filing</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('complaints.enhanced-dashboard') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="{{ route('complaints.create') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> Manual Complaint
            </a>
        </div>
    </div>


    @if($templates->isEmpty())
    <div class="bg-white rounded-lg border shadow-sm p-12 text-center text-gray-400">
        <i class="fas fa-clipboard-list text-5xl mb-4 block opacity-20"></i>
        <p class="text-lg font-medium">No templates available</p>
        <p class="text-sm mt-2">Run <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">php artisan db:seed --class=ComplaintTemplatesSeeder</code> to seed templates.</p>
    </div>
    @else
    {{-- Group by category --}}
    @foreach($templates->groupBy('category') as $category => $group)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i class="fas fa-folder text-indigo-400"></i>
            {{ ucfirst($category) }}
            <span class="text-xs font-normal bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $group->count() }}</span>
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($group as $template)
            <div class="bg-white rounded-lg border shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-semibold text-gray-800">{{ $template->name }}</h3>
                    @if($template->default_priority)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $template->default_priority->color() }}-100 text-{{ $template->default_priority->color() }}-700">
                        {{ $template->default_priority->label() }}
                    </span>
                    @endif
                </div>

                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                    {{ $template->description_template }}
                </p>

                @if($template->suggested_sla_hours)
                <p class="text-xs text-gray-500 mb-2">
                    <i class="fas fa-clock text-orange-400 mr-1"></i>
                    SLA: {{ $template->suggested_sla_hours }} hours
                </p>
                @endif

                @if($template->required_evidence_types)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 mb-1">Required evidence:</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($template->required_evidence_types as $evidType)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                            {{ ucfirst(str_replace('_', ' ', $evidType)) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Use template form --}}
                <div class="border-t pt-3 mt-3">
                    <button onclick="openTemplateModal({{ $template->id }}, '{{ addslashes($template->name) }}', '{{ addslashes($template->description_template) }}')"
                            class="w-full px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-file-alt mr-2"></i>Use This Template
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
    @endif
</div>

{{-- Template Usage Modal --}}
<div id="templateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clipboard-list text-indigo-500 mr-2"></i>
                <span id="modalTemplateName"></span>
            </h3>
            <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="templateForm" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Candidate <span class="text-red-500">*</span>
                </label>
                <select name="candidate_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Select candidate —</option>
                    @foreach(\App\Models\Candidate::orderBy('name')->limit(200)->get() as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->btevta_id }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea id="modalDescription" name="description" rows="5" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono"
                          placeholder="Fill in the template details..."></textarea>
                <p class="text-xs text-gray-400 mt-1">Replace bracketed placeholders with actual values</p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeTemplateModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>File Complaint
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openTemplateModal(templateId, templateName, descriptionTemplate) {
    document.getElementById('modalTemplateName').textContent = templateName;
    document.getElementById('modalDescription').value = descriptionTemplate;
    document.getElementById('templateForm').action = '/complaints/from-template/' + templateId;
    document.getElementById('templateModal').classList.remove('hidden');
}

function closeTemplateModal() {
    document.getElementById('templateModal').classList.add('hidden');
}
</script>
@endpush
@endsection
