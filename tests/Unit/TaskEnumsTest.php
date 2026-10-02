<?php

namespace Tests\Unit;

use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskEnumsTest extends TestCase
{
    public function test_priority_weights_increase_with_urgency(): void
    {
        $weights = array_map(fn (TaskPriority $p) => $p->weight(), TaskPriority::cases());

        $this->assertSame([1, 2, 3, 4], $weights);
    }

    public function test_priority_has_a_label_and_a_color_for_every_case(): void
    {
        foreach (TaskPriority::cases() as $priority) {
            $this->assertNotSame('', $priority->label());
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $priority->color());
        }

        $this->assertSame('Urgent', TaskPriority::Urgent->label());
    }

    public function test_task_status_values_match_the_database_strings(): void
    {
        $this->assertSame(['todo', 'in_progress', 'done'], array_column(TaskStatus::cases(), 'value'));
    }
}
