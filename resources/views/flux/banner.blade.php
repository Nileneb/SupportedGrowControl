@props(['variant' => 'info'])

@php
$variantClasses = [
    'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
    'danger' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200',
    'info' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200',
];
@endphp

<div {{ $attributes->merge(['class' => 'p-4 border rounded-lg ' . ($variantClasses[$variant] ?? $variantClasses['info'])]) }}>
    {{ $slot }}
</div>
