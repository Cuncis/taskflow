<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class LifecycleTestController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'message' => 'This came from a real controller',
            'timestamp' => now(),
        ]);
    }
}
