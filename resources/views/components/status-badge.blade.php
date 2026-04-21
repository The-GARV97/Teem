@props(['status'])

@php
    $classes = match($status) {
        'active'   => 'bg-green-100 text-green-800',
        'inactive' => 'bg-red-100 text-red-800',
        default    => 'bg-gray-100 text-gray-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ ucfirst($status) }}
</span>
