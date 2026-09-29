<?php

use App\Http\Controllers\LifecycleTestController;
use App\Http\Controllers\TaskController;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lifecycle-test', [LifecycleTestController::class, 'show']);

Route::post('/tasks', [TaskController::class, 'store']);

Route::get('/board-demo', function () {
    // $tasks = Task::all();
    // $tasks = Task::with(['project', 'assignee', 'comments'])->get();
    $tasks = Task::with(['project', 'assignee'])
        ->withCount('comments')
        ->get();

    return view('board-demo', ['tasks' => $tasks]);
});

Route::get('/projects-demo', function () {
    $projects = Project::with('tasks.assignee')->get();

    return view('projects-demo', ['projects' => $projects]);
});
