@if (session('status'))
    <flux:banner variant="success" class="mb-4">
        {{ session('status') }}
    </flux:banner>
@endif

<flux:card>
    <flux:heading size="md" class="mb-4">Quick Feedback</flux:heading>

    <form method="POST" action="{{ route('feedback.store') }}" class="space-y-4">
        @csrf

        <div>
            <flux:label for="feedback-rating">Rating (optional)</flux:label>
            <flux:select name="rating" id="feedback-rating" placeholder="Rate your experience">
                <option value="">No rating</option>
                @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}">{{ $i }} / 5 {{ str_repeat('⭐', $i) }}</option>
                @endfor
            </flux:select>
            @error('rating')
                <flux:error>{{ $message }}</flux:error>
            @enderror
        </div>

        <div>
            <flux:label for="feedback-context">Category</flux:label>
            <flux:select name="context" id="feedback-context" placeholder="Select category">
                <option value="">General</option>
                <option value="ui">UI</option>
                <option value="devices">Devices</option>
                <option value="agent">Agent</option>
                <option value="sensors">Sensors</option>
                <option value="bug">Bug</option>
                <option value="feature">Feature Request</option>
            </flux:select>
        </div>

        <div>
            <flux:label for="feedback-message">Your Feedback</flux:label>
            <flux:textarea
                name="message"
                id="feedback-message"
                rows="4"
                placeholder="Share your thoughts, report bugs, or suggest improvements…"
                required
            ></flux:textarea>
            @error('message')
                <flux:error>{{ $message }}</flux:error>
            @enderror
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            Send Feedback
        </flux:button>
    </form>
</flux:card>
