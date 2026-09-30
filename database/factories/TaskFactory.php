<?php

namespace Database\Factories;

use App\Domain\Collaboration\Models\Comment;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Domain\Task\TaskPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /** @var class-string<Task> */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assignee_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in_progress', 'done']),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->dateTimeBetween('-10 days', '+30 days'),
        ];
    }

    /** Not done, and due date already past: matches Task::overdue(). */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement(['todo', 'in_progress']),
            'due_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::High,
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => null,
        ]);
    }

    /** Overdue AND high priority, composed from the two states so their definitions stay in one place. */
    public function urgent(): static
    {
        return $this->overdue()->highPriority();
    }

    /** Attach exactly $count comments: deterministic, unlike withRandomComments(). */
    public function withComments(int $count): static
    {
        return $this->afterCreating(function (Task $task) use ($count) {
            Comment::factory()
                ->count($count)
                ->state(fn () => ['user_id' => self::existingUserId()])
                ->for($task, 'commentable')
                ->create();
        });
    }

    /**
     * About 40% of tasks get 1-3 comments, for demo data that feels alive.
     * Opt-in rather than in configure(): a hook that ran for every factory task would
     * make comment counts random in every test, and would break withComments()' exact count.
     */
    public function withRandomComments(): static
    {
        return $this->afterCreating(function (Task $task) {
            if (fake()->boolean(40)) {
                Comment::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->state(fn () => ['user_id' => self::existingUserId()])
                    ->for($task, 'commentable')
                    ->create();
            }
        });
    }

    /**
     * Commenters come from users that already exist. CommentFactory's own default would create a
     * brand-new user per comment, which silently inflates the user table (seeding 6 users gave 40+).
     * Falls back to that default (null => new user) only when no user exists yet.
     */
    private static function existingUserId(): int|Factory|null
    {
        return User::query()->inRandomOrder()->value('id') ?? User::factory();
    }
}
