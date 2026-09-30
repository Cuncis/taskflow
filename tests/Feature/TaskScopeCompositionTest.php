<?php

namespace Tests\Feature;

use App\Domain\Task\TaskPriority;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskScopeCompositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_priority_and_assignee_scopes_compose(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $base = [
            'status' => 'todo',
            'priority' => TaskPriority::High->value,
            'due_date' => today()->subDays(2),
            'assignee_id' => $user->id,
        ];

        $match = Task::factory()->create($base);
        $alsoMatch = Task::factory()->create([...$base, 'status' => 'in_progress']);

        // Each of these fails exactly one of the three scopes.
        Task::factory()->create([...$base, 'due_date' => today()->addDays(2)]);   // not overdue
        Task::factory()->create([...$base, 'status' => 'done']);                  // not overdue (done)
        Task::factory()->create([...$base, 'priority' => TaskPriority::Medium->value]); // priority too low
        Task::factory()->create([...$base, 'assignee_id' => $other->id]);         // other assignee

        $result = Task::overdue()
            ->priorityAtLeast(TaskPriority::High)
            ->assignedTo($user)
            ->get();

        $this->assertEqualsCanonicalizing([$match->id, $alsoMatch->id], $result->pluck('id')->all());
    }

    public function test_scopes_are_order_independent(): void
    {
        $user = User::factory()->create();
        Task::factory()->create([
            'status' => 'todo',
            'priority' => TaskPriority::High->value,
            'due_date' => today()->subDay(),
            'assignee_id' => $user->id,
        ]);

        $a = Task::overdue()->priorityAtLeast(TaskPriority::High)->assignedTo($user)->pluck('id')->all();
        $b = Task::assignedTo($user)->priorityAtLeast(TaskPriority::High)->overdue()->pluck('id')->all();

        $this->assertNotEmpty($a);
        $this->assertSame($a, $b);
    }
}
