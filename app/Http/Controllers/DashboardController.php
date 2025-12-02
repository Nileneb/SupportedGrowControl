<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $devices = $user->devices;
        $totalDevices = $devices->count();
        $onlineDevices = $devices->where('status', 'online')->count();
        $pairedDevices = $devices->where('status', 'paired')->count();
        
        return view('dashboard', compact('devices', 'totalDevices', 'onlineDevices', 'pairedDevices'));
    }
}
