@props(['sensor', 'latestReading' => null])

@php
    $category = $sensor['category'] ?? 'unknown';
    $name = $sensor['name'] ?? 'Unknown Sensor';
    $unit = $sensor['unit'] ?? '';
    $value = $latestReading?->value ?? null;
    $timestamp = $latestReading?->created_at ?? null;
    
    // Define display config per category
    $config = [
        'temperature' => [
            'icon' => '🌡️',
            'color' => 'red',
            'min' => 0,
            'max' => 40,
            'decimals' => 1,
        ],
        'humidity' => [
            'icon' => '💧',
            'color' => 'blue',
            'min' => 0,
            'max' => 100,
            'decimals' => 0,
        ],
        'tds' => [
            'icon' => '⚗️',
            'color' => 'purple',
            'min' => 0,
            'max' => 2000,
            'decimals' => 0,
        ],
        'water_level' => [
            'icon' => '🌊',
            'color' => 'cyan',
            'min' => 0,
            'max' => 100,
            'decimals' => 0,
        ],
        'ph' => [
            'icon' => '🧪',
            'color' => 'green',
            'min' => 0,
            'max' => 14,
            'decimals' => 1,
        ],
        'light' => [
            'icon' => '☀️',
            'color' => 'yellow',
            'min' => 0,
            'max' => 1000,
            'decimals' => 0,
        ],
    ];
    
    $c = $config[$category] ?? $config['temperature'];
    $percentage = $value !== null ? min(100, max(0, (($value - $c['min']) / ($c['max'] - $c['min'])) * 100)) : 0;
@endphp

<div class="relative rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 hover:shadow-lg transition-shadow">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="text-2xl">{{ $c['icon'] }}</span>
            <div>
                <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $name }}</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ ucfirst($category) }}</p>
            </div>
        </div>
        @if($timestamp)
            <span class="text-xs text-neutral-400 dark:text-neutral-500">{{ $timestamp->diffForHumans() }}</span>
        @endif
    </div>
    
    <!-- Value Display -->
    <div class="mb-4">
        @if($value !== null)
            <div class="text-4xl font-bold text-neutral-900 dark:text-neutral-100">
                {{ number_format($value, $c['decimals']) }}
                <span class="text-xl text-neutral-500 dark:text-neutral-400">{{ $unit }}</span>
            </div>
        @else
            <div class="text-2xl text-neutral-400 dark:text-neutral-500">No data</div>
        @endif
    </div>
    
    <!-- Visual Gauge -->
    <div class="space-y-2">
        <div class="flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
            <span>{{ $c['min'] }}{{ $unit }}</span>
            <span>{{ $c['max'] }}{{ $unit }}</span>
        </div>
        <div class="relative h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
            <div 
                class="absolute h-full rounded-full transition-all duration-500 
                    @if($c['color'] === 'red') bg-gradient-to-r from-red-500 to-red-600
                    @elseif($c['color'] === 'blue') bg-gradient-to-r from-blue-500 to-blue-600
                    @elseif($c['color'] === 'purple') bg-gradient-to-r from-purple-500 to-purple-600
                    @elseif($c['color'] === 'cyan') bg-gradient-to-r from-cyan-500 to-cyan-600
                    @elseif($c['color'] === 'green') bg-gradient-to-r from-green-500 to-green-600
                    @elseif($c['color'] === 'yellow') bg-gradient-to-r from-yellow-500 to-yellow-600
                    @endif"
                style="width: {{ $percentage }}%"
            ></div>
        </div>
    </div>
    
    <!-- Status Indicator -->
    <div class="mt-3 flex items-center gap-2">
        @if($value !== null)
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-xs text-green-600 dark:text-green-400">Active</span>
        @else
            <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
            <span class="text-xs text-gray-500 dark:text-gray-400">Waiting for data</span>
        @endif
    </div>
</div>
