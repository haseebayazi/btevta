<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Stories — WASL BTEVTA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

{{-- Hero Banner --}}
<div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-16 px-4">
    <div class="container mx-auto max-w-5xl text-center">
        <i class="fas fa-star text-yellow-400 text-5xl mb-4"></i>
        <h1 class="text-4xl font-bold mb-3">Success Stories</h1>
        <p class="text-blue-200 text-lg max-w-2xl mx-auto">
            Real stories of Pakistani workers who transformed their lives through BTEVTA's overseas employment programme.
        </p>
    </div>
</div>

{{-- Type filter --}}
<div class="bg-white border-b sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto max-w-5xl px-4 py-3">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('success-stories.public') }}"
               class="flex-shrink-0 px-4 py-1.5 rounded-full text-sm {{ !request('type') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                All Stories
            </a>
            @foreach($storyTypes as $type)
            <a href="{{ route('success-stories.public', ['type' => $type->value]) }}"
               class="flex-shrink-0 px-4 py-1.5 rounded-full text-sm flex items-center gap-1 {{ request('type') == $type->value ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                <i class="{{ $type->icon() }}"></i> {{ $type->label() }}
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Stories Grid --}}
<div class="container mx-auto max-w-5xl px-4 py-10">
    @if($stories->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <i class="fas fa-star text-5xl mb-4 block opacity-20"></i>
        <p class="text-lg">No stories published yet.</p>
    </div>
    @else
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($stories as $story)
        <article class="bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow overflow-hidden group">
            {{-- Card Header --}}
            <div class="h-32 bg-gradient-to-br from-{{ $story->story_type?->color() ?? 'blue' }}-400 to-{{ $story->story_type?->color() ?? 'indigo' }}-600 flex items-center justify-center relative">
                <i class="{{ $story->story_type?->icon() ?? 'fas fa-star' }} text-white text-4xl opacity-80"></i>
                @if($story->is_featured)
                <div class="absolute top-3 right-3">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                        <i class="fas fa-star"></i> Featured
                    </span>
                </div>
                @endif
            </div>

            <div class="p-5">
                {{-- Type Badge --}}
                @if($story->story_type)
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-{{ $story->story_type->color() }}-100 text-{{ $story->story_type->color() }}-700 mb-3">
                    <i class="{{ $story->story_type->icon() }}"></i> {{ $story->story_type->label() }}
                </span>
                @endif

                {{-- Headline --}}
                <h3 class="font-bold text-gray-800 text-base leading-snug mb-2">
                    {{ $story->headline ?: Str::limit($story->written_note, 60) }}
                </h3>

                {{-- Candidate & Country --}}
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <i class="fas fa-user-circle text-gray-400"></i>
                    <span>{{ $story->candidate->name ?? 'Anonymous' }}</span>
                    @if($story->country)
                    <span class="text-gray-300">|</span>
                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                    <span>{{ $story->country->name }}</span>
                    @endif
                </div>

                {{-- Employment details --}}
                @if($story->employer_name || $story->salary_achieved)
                <div class="flex flex-wrap gap-3 text-xs mb-3">
                    @if($story->employer_name)
                    <span class="flex items-center gap-1 text-gray-600">
                        <i class="fas fa-building text-gray-400"></i> {{ $story->employer_name }}
                    </span>
                    @endif
                    @if($story->salary_achieved)
                    <span class="flex items-center gap-1 text-green-600 font-semibold">
                        <i class="fas fa-money-bill-wave"></i>
                        {{ number_format($story->salary_achieved) }} {{ $story->salary_currency }}
                    </span>
                    @endif
                </div>
                @endif

                {{-- Story excerpt --}}
                <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">
                    {{ Str::limit($story->written_note, 120) }}
                </p>

                {{-- Footer --}}
                <div class="flex justify-between items-center mt-4 pt-4 border-t text-xs text-gray-400">
                    <span>{{ $story->published_at?->format('d M Y') ?? $story->created_at->format('d M Y') }}</span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-eye"></i> {{ number_format($story->views_count) }}
                    </span>
                </div>
            </div>
        </article>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($stories->hasPages())
    <div class="mt-10">
        {{ $stories->withQueryString()->links() }}
    </div>
    @endif
    @endif
</div>

{{-- Footer --}}
<footer class="bg-gray-800 text-gray-400 py-8 mt-10">
    <div class="container mx-auto px-4 text-center text-sm">
        <p>&copy; {{ date('Y') }} BTEVTA — Board of Technical Education &amp; Vocational Training Authority, Punjab</p>
        <p class="mt-1">WASL — Workforce Abroad Skills &amp; Linkages</p>
    </div>
</footer>

</body>
</html>
