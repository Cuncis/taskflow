<?php

namespace App\Http\Controllers;

use App\Domain\Task\TaskService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskService->createTask($request->validated());


        // 5. Email notification (pretend this sends a real email)
        // Mail::to('team@taskflow.test')->send(new TaskCreatedMail($task));

        // 6. Response formatting
        return response()->json([
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }
}
