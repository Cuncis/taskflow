<?php

namespace Tests\Feature;

use App\Domain\Task\Http\Resources\TaskResource;
use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_loaded_but_missing_assignee_serializes_as_null(): void
    {
        $task = Task::factory()->unassigned()->create()->load('assignee');

        $data = (new TaskResource($task))->toArray(new Request);

        $this->assertArrayHasKey('assignee', $data);
        $this->assertNull($data['assignee']);
    }

    public function test_the_assignee_is_omitted_when_the_relation_is_not_loaded(): void
    {
        $task = Task::factory()->create();

        $json = (new TaskResource($task))->resolve(new Request);

        $this->assertArrayNotHasKey('assignee', $json);
    }
}
