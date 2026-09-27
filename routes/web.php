<?php

use App\Http\Controllers\LifecycleTestController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lifecycle-test', [LifecycleTestController::class, 'show']);

Route::post('/tasks', [TaskController::class, 'store']);
