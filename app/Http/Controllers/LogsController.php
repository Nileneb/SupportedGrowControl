<?php

namespace App\Http\Controllers;

use App\Models\DeviceLog;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{
    /**
     * Show central logs page
     */
    public function index()
    {
        $devices = Device::where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('logs.index', compact('devices'));
    }

    /**
     * Get all logs from all user devices (API)
     */
    public function all(Request $request)
    {
        $limit = min((int) $request->get('limit', 500), 1000);
        
        $logs = DeviceLog::whereHas('device', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with('device:id,name')
            // Sort by agent_timestamp if available, otherwise by created_at
            ->orderByRaw('COALESCE(agent_timestamp, created_at) DESC')
            ->limit($limit)
            ->get();

        return response()->json([
            'logs' => $logs,
            'count' => $logs->count(),
        ]);
    }

    /**
     * Clear all logs for user's devices
     */
    public function clear(Request $request)
    {
        $deleted = DeviceLog::whereHas('device', function ($q) {
            $q->where('user_id', Auth::id());
        })->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }
}
