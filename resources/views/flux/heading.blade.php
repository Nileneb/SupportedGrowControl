@props(['size' => 'md'])

@php
$sizeClasses = [
    'sm' => 'text-sm font-medium',
    'md' => 'text-base font-semibold',
    'lg' => 'text-lg font-bold',
    'xl' => 'text-xl font-bold',
];
@endphp

<h2 {{ $attributes->merge(['class' => ($sizeClasses[$size] ?? $sizeClasses['md']) . ' text-gray-900 dark:text-gray-100']) }}>
    {{ $slot }}
</h2>
