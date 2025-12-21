{{--
    Reusable Card Component
    Usage:
    @component('components.card', ['title' => 'My Title', 'icon' => 'fas fa-user'])
        Card content here
    @endcomponent

    Or with attributes:
    @component('components.card', [
        'title' => 'My Title',
        'icon' => 'fas fa-user',
        'footer' => true,
        'class' => 'mb-4',
        'headerClass' => 'bg-blue-50',
        'padding' => false
    ])
        Card content
        @slot('footer')
            Footer content
        @endslot
    @endcomponent
--}}

@php
    $padding = $padding ?? true;
    $headerClass = $headerClass ?? '';
    $cardClass = $class ?? '';
@endphp

<div class="bg-white rounded-lg shadow-sm overflow-hidden {{ $cardClass }}">
    @if(isset($title))
        <div class="px-6 py-4 border-b border-gray-200 {{ $headerClass }}">
            <h3 class="text-lg font-semibold text-gray-800">
                @if(isset($icon))
                    <i class="{{ $icon }} mr-2 text-blue-600"></i>
                @endif
                {{ $title }}
            </h3>
        </div>
    @endif

    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $footer }}
        </div>
    @endif
</div>
