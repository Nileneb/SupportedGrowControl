<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        $query = DeviceLog::query()
            ->with('device:id,public_id,name')
            ->orderBy('created_at', 'desc');

        // Filter by device if specified
        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by level if specified
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Search in message
        if ($request->has('search')) {
            $query->where('message', 'ILIKE', '%' . $request->search . '%');
        }

        // Paginate results
        $logs = $query->paginate(100);

        // Get all devices for filter dropdown
        $devices = Device::where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'public_id', 'name']);

        // Count by level
        $levelCounts = DeviceLog::select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level');

        return view('logs.index', compact('logs', 'devices', 'levelCounts'));
    }

    public function clear(Request $request)
    {
        $deleted = DeviceLog::query();

        if ($request->has('device_id')) {
            $deleted->where('device_id', $request->device_id);
        }

        if ($request->has('level')) {
            $deleted->where('level', $request->level);
        }

        $count = $deleted->delete();

        return redirect()->route('logs.index')->with('success', "Deleted {$count} log entries");
    }
}
