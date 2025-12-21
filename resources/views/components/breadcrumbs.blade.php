{{--
    Breadcrumbs Component
    Usage:
    @include('components.breadcrumbs', [
        'items' => [
            ['label' => 'Dashboard', 'route' => 'dashboard.index'],
            ['label' => 'Candidates', 'route' => 'candidates.index'],
            ['label' => 'Create'] // Last item (no route = current page)
        ]
    ])
--}}

<nav class="text-sm mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2 flex-wrap">
        @foreach($items ?? [] as $index => $item)
            @if($loop->last)
                {{-- Current page (not a link) --}}
                <li class="text-gray-600 font-medium">
                    @if(isset($item['icon']))
                        <i class="{{ $item['icon'] }} mr-1"></i>
                    @endif
                    {{ $item['label'] }}
                </li>
            @else
                {{-- Link to previous page --}}
                <li class="flex items-center">
                    <a href="{{ isset($item['route']) ? route($item['route'], $item['params'] ?? []) : '#' }}"
                       class="text-blue-600 hover:text-blue-800 transition">
                        @if(isset($item['icon']))
                            <i class="{{ $item['icon'] }} mr-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                    <span class="text-gray-400 mx-2">/</span>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
