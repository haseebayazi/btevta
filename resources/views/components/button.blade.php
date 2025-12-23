{{--
    Reusable Button Component
    Usage:
    @include('components.button', ['type' => 'primary', 'text' => 'Submit', 'icon' => 'fas fa-save'])
    @include('components.button', ['type' => 'danger', 'text' => 'Delete', 'confirm' => 'Are you sure?'])
    @include('components.button', ['type' => 'link', 'href' => route('home'), 'text' => 'Go Home'])

    Types: primary, secondary, success, danger, warning, info
--}}

@php
    $type = $type ?? 'primary';
    $tag = isset($href) ? 'a' : 'button';
    $buttonType = isset($href) ? null : ($submit ?? 'button');

    $styles = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-700 focus:ring-gray-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white focus:ring-yellow-500',
        'info' => 'bg-cyan-600 hover:bg-cyan-700 text-white focus:ring-cyan-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $style = $styles[$type] ?? $styles['primary'];
    $sizeClass = $sizes[$size ?? 'md'];
    $baseClass = "inline-flex items-center justify-center rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed {$style} {$sizeClass}";
    $extraClass = $class ?? '';
@endphp

<{{ $tag }}
    @if($tag === 'a') href="{{ $href }}" @endif
    @if($buttonType) type="{{ $buttonType }}" @endif
    @if(isset($confirm)) data-confirm-delete="{{ $confirm }}" @endif
    @if(isset($disabled) && $disabled) disabled @endif
    @if(isset($id)) id="{{ $id }}" @endif
    class="{{ $baseClass }} {{ $extraClass }}"
>
    @if(isset($icon))
        <i class="{{ $icon }} {{ isset($text) ? 'mr-2' : '' }}"></i>
    @endif
    {{ $text ?? '' }}
</{{ $tag }}>
