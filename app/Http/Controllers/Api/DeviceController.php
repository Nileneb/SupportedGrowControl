<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends DeviceRegistrationController
{
    /**
     * Register a device (alias for registerFromAgent).
     * POST /api/growdash/devices/register
     */
    public function register(Request $request): JsonResponse
    {
        return $this->registerFromAgent($request);
    }
}
