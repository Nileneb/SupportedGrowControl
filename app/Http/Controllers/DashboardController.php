<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return redirect()->route('login');
            }
            
            $devices = $user->devices()->get();
            $totalDevices = $devices->count();
            $onlineDevices = $devices->where('status', 'online')->count();
            $pairedDevices = $devices->where('status', 'paired')->count();
            
            return view('dashboard', compact('devices', 'totalDevices', 'onlineDevices', 'pairedDevices'));
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id
            ]);
            
            // Fallback to empty dashboard
            return view('dashboard', [
                'devices' => collect([]),
                'totalDevices' => 0,
                'onlineDevices' => 0,
                'pairedDevices' => 0
            ]);
        }
    }
}
