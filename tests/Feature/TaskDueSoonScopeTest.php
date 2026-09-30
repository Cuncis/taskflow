<?php

namespace Tests\Feature;

use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskDueSoonScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tasks due at -1, 0, 1, 3, 5 and 10 days from today (all not done).
     *
     * @return array<string, array{?int, list<int>}>
     */
    public static function daysProvider(): array
    {
        return [
            'default is 3 days' => [null, [0, 1, 3]],
            '0 days = today only' => [0, [0]],
            '1 day' => [1, [0, 1]],
            '5 days' => [5, [0, 1, 3, 5]],
            '30 days' => [30, [0, 1, 3, 5, 10]],
        ];
    }

    /**
     * @param  list<int>  $expectedOffsets
     */
    #[DataProvider('daysProvider')]
    public function test_due_soon_respects_days_window(?int $days, array $expectedOffsets): void
    {
        foreach ([-1, 0, 1, 3, 5, 10] as $offset) {
            Task::factory()->create([
                'status' => 'todo',
                'due_date' => today()->addDays($offset),
            ]);
        }

        $query = $days === null ? Task::dueSoon() : Task::dueSoon($days);

        $actual = $query->pluck('due_date')
            ->map(fn ($date) => (int) today()->diffInDays($date, false))
            ->sort()->values()->all();

        $this->assertSame($expectedOffsets, $actual);
    }

    public function test_due_soon_excludes_done_tasks_and_tasks_without_due_date(): void
    {
        Task::factory()->create(['status' => 'done', 'due_date' => today()->addDay()]);
        Task::factory()->create(['status' => 'todo', 'due_date' => null]);
        $included = Task::factory()->create(['status' => 'in_progress', 'due_date' => today()->addDay()]);

        $this->assertSame([$included->id], Task::dueSoon()->pluck('id')->all());
    }
}
