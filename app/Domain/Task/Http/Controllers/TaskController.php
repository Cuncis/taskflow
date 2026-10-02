<?php

namespace App\Domain\Task\Http\Controllers;

use App\Domain\Task\Actions\ArchiveTaskAction;
use App\Domain\Task\Actions\AssignTaskAction;
use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Http\Requests\AssignTaskRequest;
use App\Domain\Task\Http\Requests\StoreTaskRequest;
use App\Domain\Task\Http\Resources\TaskResource;
use App\Domain\Task\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, CreateTaskAction $createTask): JsonResponse
    {
        $task = $createTask($request->validated());

        return response()->json([
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }

    public function assign(AssignTaskRequest $request, Task $task, AssignTaskAction $assignTask): JsonResponse
    {
        $assignee = User::findOrFail($request->integer('assignee_id'));

        $task = $assignTask($task, $assignee)->load('assignee');

        return response()->json([
            'message' => 'Task assigned successfully',
            'data' => new TaskResource($task),
        ]);
    }

    public function archive(Task $task, ArchiveTaskAction $archiveTask): JsonResponse
    {
        try {
            $task = $archiveTask($task);
        } catch (RuntimeException $e) {
            // Business-rule violation (e.g. task isn't done): a client error, not a server 500.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Task archived successfully',
            'data' => new TaskResource($task),
        ]);
    }
}
