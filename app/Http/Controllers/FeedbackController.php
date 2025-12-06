<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();

        Log::info('Feedback submission attempt', [
            'user_id' => $user->id,
            'has_message' => $request->has('message'),
            'message_value' => $request->input('message'),
        ]);

        Feedback::create([
            'user_id' => $user->id,
            'rating'  => $request->input('rating'),
            'context' => $request->input('context'),
            'message' => $request->input('message'),
            'meta'    => [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path'       => $request->path(),
                'referer'    => $request->header('referer'),
            ],
        ]);

        Log::info('Feedback created successfully');
        Log::info('🎯 ENDPOINT_TRACKED: FeedbackController@store', [
            'user_id' => $user->id,
            'rating' => $request->input('rating'),
        ]);

        return back()->with('status', 'Thank you for your feedback! We appreciate your input.');
    }
}
