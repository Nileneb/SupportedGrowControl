<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExitRemoteSupport extends Component
{
    public function render()
    {
        return view('livewire.exit-remote-support');
    }

    public function exitRemoteSupport()
    {
        if (!session()->has('admin_user_id') || !session()->has('remote_support_active')) {
            return redirect()->route('dashboard');
        }

        $adminUserId = session()->get('admin_user_id');
        
        // Clear remote support session
        session()->forget(['admin_user_id', 'remote_support_active']);
        
        // Log back in as admin
        Auth::loginUsingId($adminUserId);
        
        return redirect()->route('admin.users')->with('message', 'Remote support session ended.');
    }
}
