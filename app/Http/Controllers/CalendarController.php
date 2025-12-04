<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use RRule\RRule;

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

        $startC = $start ? Carbon::parse($start) : Carbon::now()->startOfMonth();
        $endC = $end ? Carbon::parse($end) : Carbon::now()->endOfMonth();

        $expanded = [];
        foreach ($query->orderBy('start_at')->get() as $e) {
            // Compute base duration (seconds) from end_at or meta params
            $durationSeconds = null;
            if ($e->start_at && $e->end_at) {
                $durationSeconds = $e->end_at->diffInSeconds($e->start_at);
            } else {
                $meta = $e->meta ?? [];
                $params = is_array($meta) ? ($meta['params'] ?? []) : [];
                if (isset($params['duration_ms'])) {
                    $durationSeconds = (int) $params['duration_ms'] / 1000;
                }
            }

            if (!empty($e->rrule) && $e->start_at) {
                try {
                    $rr = new RRule($e->rrule, $e->start_at->toDateTimeString());
                    $occ = $rr->getOccurrencesBetween($startC->toDateTimeString(), $endC->toDateTimeString());
                    foreach ($occ as $occurrence) {
                        $occStart = Carbon::instance($occurrence);
                        $occEnd = null;
                        if ($durationSeconds) {
                            $occEnd = $occStart->copy()->addSeconds($durationSeconds);
                        }
                        $expanded[] = [
                            'id' => $e->id,
                            'title' => $e->title,
                            'start_at' => $occStart->toIso8601String(),
                            'end_at' => $occEnd?->toIso8601String(),
                            'all_day' => (bool) $e->all_day,
                            'status' => $e->status,
                            'device_id' => $e->device_id,
                            'calendar_id' => $e->calendar_id,
                            'color' => $e->color,
                        ];
                    }
                } catch (\Throwable $ex) {
                    // Fallback: include base event if intersects the window
                    if ($e->start_at && $e->start_at->between($startC, $endC)) {
                        $expanded[] = [
                            'id' => $e->id,
                            'title' => $e->title,
                            'start_at' => $e->start_at?->toIso8601String(),
                            'end_at' => $e->end_at?->toIso8601String(),
                            'all_day' => (bool) $e->all_day,
                            'status' => $e->status,
                            'device_id' => $e->device_id,
                            'calendar_id' => $e->calendar_id,
                            'color' => $e->color,
                        ];
                    }
                }
            } else {
                // Non-recurring: include if within the window
                if ($e->start_at && $e->start_at->between($startC, $endC)) {
                    $expanded[] = [
                        'id' => $e->id,
                        'title' => $e->title,
                        'start_at' => $e->start_at?->toIso8601String(),
                        'end_at' => $e->end_at?->toIso8601String(),
                        'all_day' => (bool) $e->all_day,
                        'status' => $e->status,
                        'device_id' => $e->device_id,
                        'calendar_id' => $e->calendar_id,
                        'color' => $e->color,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'events' => $expanded,
        ]);
    }
}
