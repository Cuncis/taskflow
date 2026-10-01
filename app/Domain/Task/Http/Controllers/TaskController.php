<?php

namespace App\Domain\Task\Http\Controllers;

use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Http\Requests\StoreTaskRequest;
use App\Domain\Task\Http\Resources\TaskResource;
use App\Http\Controllers\Controller;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, CreateTaskAction $createTask)
    {
        $task = $createTask($request->validated());

        return response()->json([
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }
}
