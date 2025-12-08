<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Allow broadcast auth without forcing the auth middleware; channel callbacks still decide access
        Broadcast::routes(['middleware' => ['web']]);

        require base_path('routes/channels.php');
    }
}
