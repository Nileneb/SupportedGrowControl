@props(['name' => '', 'active' => false])

<button
    type="button"
    {{ $attributes->merge(['class' => 'px-3 py-2 font-medium text-sm rounded-md ' . ($active ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300')]) }}
>
    {{ $slot }}
</button>
