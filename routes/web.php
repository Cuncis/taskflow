<?php

use App\Domain\Project\Models\Project;
use App\Domain\Task\Http\Controllers\TaskController;
use App\Domain\Task\Models\Task;
use App\Http\Controllers\LifecycleTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lifecycle-test', [LifecycleTestController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}/assign', [TaskController::class, 'assign']);
    Route::delete('/tasks/{task}/archive', [TaskController::class, 'archive']);
});

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
