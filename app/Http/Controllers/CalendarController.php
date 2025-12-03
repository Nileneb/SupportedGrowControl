<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(Request $request)
    {
        $user = Auth::user();
        $start = $request->date('start');
        $end = $request->date('end');

        $query = Event::where('user_id', $user->id);
        if ($start) $query->where('start_at', '>=', $start);
        if ($end) $query->where('start_at', '<', $end);

        return response()->json([
            'success' => true,
            'events' => $query->orderBy('start_at')->get(),
        ]);
    }
}
