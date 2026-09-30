<?php

namespace App\Http\Controllers;

class LifecycleTestController extends Controller
{
    public function show()
    {
        return response()->json([
            'message' => 'This came from a real controller',
            'timestamp' => now(),
        ]);
    }
}
