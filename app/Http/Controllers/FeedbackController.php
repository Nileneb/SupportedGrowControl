<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();

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

        return back()->with('status', 'Thank you for your feedback! We appreciate your input.');
    }
}
