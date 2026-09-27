<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LifecycleTestController extends Controller
{
    public function show()
    {
        return response()->json([
            'message' => 'This came from a real controller',
            'timestamp' => now()
        ]);
    }
}
