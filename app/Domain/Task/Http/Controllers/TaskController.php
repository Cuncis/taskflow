<?php

namespace App\Domain\Task\Http\Controllers;

use App\Domain\Task\Actions\AssignTaskAction;
use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Http\Requests\AssignTaskRequest;
use App\Domain\Task\Http\Requests\StoreTaskRequest;
use App\Domain\Task\Http\Resources\TaskResource;
use App\Domain\Task\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\User;

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

    public function assign(AssignTaskRequest $request, Task $task, AssignTaskAction $assignTask)
    {
        $assignee = User::findOrFail($request->validated('assignee_id'));

        $task = $assignTask($task, $assignee);

        return response()->json([
            'message' => 'Task assigned successfully',
            'data' => new TaskResource($task),
        ]);
    }
}
