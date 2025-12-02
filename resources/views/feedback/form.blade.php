<x-layouts.app :title="__('Send Feedback')">
    <div class="max-w-2xl mx-auto">
        <flux:heading size="xl" class="mb-2">Send Feedback</flux:heading>
        <flux:subheading class="mb-6">
            Help us improve GrowDash by sharing your thoughts, suggestions, or reporting issues.
        </flux:subheading>

        @if (session('status'))
            <flux:banner variant="success" class="mb-6">
                {{ session('status') }}
            </flux:banner>
        @endif

        <flux:card>
            <form method="POST" action="{{ route('feedback.store') }}" class="space-y-6">
                @csrf

                {{-- Rating --}}
                <div>
                    <flux:label for="rating">Rating (optional)</flux:label>
                    <flux:select name="rating" id="rating" placeholder="Select a rating">
                        <option value="">No rating</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected(old('rating') == $i)>
                                {{ $i }} / 5 {{ str_repeat('⭐', $i) }}
                            </option>
                        @endfor
                    </flux:select>
                    @error('rating')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Context --}}
                <div>
                    <flux:label for="context">Category (optional)</flux:label>
                    <flux:select name="context" id="context" placeholder="Select a category">
                        <option value="">General feedback</option>
                        <option value="ui" @selected(old('context') === 'ui')>User Interface</option>
                        <option value="devices" @selected(old('context') === 'devices')>Device Management</option>
                        <option value="agent" @selected(old('context') === 'agent')>Python Agent</option>
                        <option value="sensors" @selected(old('context') === 'sensors')>Sensors & Actuators</option>
                        <option value="hardware" @selected(old('context') === 'hardware')>Hardware Setup</option>
                        <option value="documentation" @selected(old('context') === 'documentation')>Documentation</option>
                        <option value="bug" @selected(old('context') === 'bug')>Bug Report</option>
                        <option value="feature" @selected(old('context') === 'feature')>Feature Request</option>
                    </flux:select>
                    @error('context')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Message --}}
                <div>
                    <flux:label for="message">Your Feedback</flux:label>
                    <flux:textarea
                        name="message"
                        id="message"
                        rows="6"
                        placeholder="Tell us what worked well, what was confusing, or what you'd like to see improved…"
                        required
                    >{{ old('message') }}</flux:textarea>
                    <flux:description>
                        Please be as specific as possible. For bug reports, include steps to reproduce.
                    </flux:description>
                    @error('message')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">
                        Send Feedback
                    </flux:button>
                    <flux:button type="button" variant="ghost" href="{{ route('dashboard') }}">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Info Box --}}
        <flux:card class="mt-6">
            <flux:heading size="sm" class="mb-2">Your Privacy</flux:heading>
            <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                Your feedback is associated with your account and includes basic technical information (IP address, browser) to help us investigate issues. We never share your feedback publicly without permission.
            </flux:text>
        </flux:card>
    </div>
</x-layouts.app>
