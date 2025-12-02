<x-layouts.app :title="__('Feedback Management')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Feedback Management</flux:heading>
                <flux:subheading>Review and manage user feedback</flux:subheading>
            </div>
        </div>

        @if (session('success'))
            <flux:banner variant="success">
                {{ session('success') }}
            </flux:banner>
        @endif

        {{-- Stats Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Feedback</div>
                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            </flux:card>

            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">With Rating</div>
                <div class="text-2xl font-bold">{{ $stats['with_rating'] }}</div>
            </flux:card>

            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Avg Rating</div>
                <div class="text-2xl font-bold">
                    @if ($stats['avg_rating'])
                        {{ number_format($stats['avg_rating'], 1) }} ⭐
                    @else
                        N/A
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Categories</div>
                <div class="text-2xl font-bold">{{ $contexts->count() }}</div>
            </flux:card>
        </div>

        {{-- Filters --}}
        <flux:card>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <flux:label>Filter by Category</flux:label>
                    <flux:select wire:model.live="filterContext" placeholder="All categories">
                        <option value="">All categories</option>
                        @foreach ($contexts as $context)
                            <option value="{{ $context }}">{{ ucfirst($context) }} ({{ $stats['by_context'][$context] ?? 0 }})</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <flux:label>Filter by Rating</flux:label>
                    <flux:select wire:model.live="filterRating" placeholder="All ratings">
                        <option value="">All ratings</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} ⭐</option>
                        @endfor
                    </flux:select>
                </div>

                @if ($filterContext || $filterRating)
                    <flux:button variant="ghost" wire:click="clearFilters">
                        Clear Filters
                    </flux:button>
                @endif
            </div>
        </flux:card>

        {{-- Feedback List --}}
        <flux:card>
            @if ($feedbacks->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No feedback found.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($feedbacks as $feedback)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-sm font-semibold">
                                        {{ $feedback->user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $feedback->user->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $feedback->user->email }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($feedback->rating)
                                        <flux:badge color="amber">
                                            {{ $feedback->rating }} ⭐
                                        </flux:badge>
                                    @endif

                                    @if ($feedback->context)
                                        <flux:badge>
                                            {{ ucfirst($feedback->context) }}
                                        </flux:badge>
                                    @endif

                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        
                                        <flux:menu>
                                            <flux:menu.item 
                                                wire:click="delete({{ $feedback->id }})"
                                                wire:confirm="Are you sure you want to delete this feedback?"
                                                icon="trash"
                                            >
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>

                            {{-- Message --}}
                            <div class="mb-3 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                {{ $feedback->message }}
                            </div>

                            {{-- Meta Info --}}
                            <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <div>
                                    <strong>Date:</strong> {{ $feedback->created_at->format('M d, Y H:i') }}
                                </div>
                                @if ($feedback->meta['ip'] ?? null)
                                    <div>
                                        <strong>IP:</strong> {{ $feedback->meta['ip'] }}
                                    </div>
                                @endif
                                @if ($feedback->meta['path'] ?? null)
                                    <div>
                                        <strong>Page:</strong> {{ $feedback->meta['path'] }}
                                    </div>
                                @endif
                                @if ($feedback->meta['user_agent'] ?? null)
                                    <div class="max-w-md truncate">
                                        <strong>Browser:</strong> {{ $feedback->meta['user_agent'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $feedbacks->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts.app>
