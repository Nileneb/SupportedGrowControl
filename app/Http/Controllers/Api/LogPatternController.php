<?php

namespace App\Http\Controllers\Api;

use App\Models\LogPattern;
use App\Http\Controllers\Controller;

class LogPatternController extends Controller
{
    /**
     * Get all active log patterns for frontend parsing
     */
    public function index()
    {
        $patterns = LogPattern::getActive()
            ->map(function ($pattern) {
                return [
                    'id' => $pattern->id,
                    'name' => $pattern->name,
                    'regex' => $pattern->regex_pattern,
                    'icon' => $pattern->icon,
                    'color' => $pattern->color,
                    'parser_config' => $pattern->parser_config,
                    'priority' => $pattern->priority,
                ];
            });

        return response()->json([
            'patterns' => $patterns,
        ]);
    }
}
