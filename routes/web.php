<?php

use App\Http\Controllers\LifecycleTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lifecycle-test', [LifecycleTestController::class, 'show']);
