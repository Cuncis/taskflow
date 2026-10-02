<?php

namespace Tests\Feature;

use App\Domain\Collaboration\Models\Attachment;
use App\Domain\Collaboration\Models\Comment;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_slug_is_generated_from_the_title_on_create(): void
    {
        $task = Task::factory()->create(['title' => 'Fix The Login Bug', 'slug' => null]);

        $this->assertSame('fix-the-login-bug', $task->slug);
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        Task::factory()->create(['title' => 'Same title', 'slug' => null]);
        $second = Task::factory()->create(['title' => 'Same title', 'slug' => null]);

        $this->assertSame('same-title-1', $second->slug);
    }

    public function test_slugs_stay_unique_even_against_tasks_hidden_by_an_archived_project(): void
    {
        $archived = Project::factory()->archived()->create();
        Task::factory()->create(['title' => 'Write docs', 'slug' => null, 'project_id' => $archived->id]);

        $second = Task::factory()->create(['title' => 'Write docs', 'slug' => null, 'project_id' => null]);

        $this->assertSame('write-docs-1', $second->slug);
    }

    public function test_changing_the_title_regenerates_the_slug(): void
    {
        $task = Task::factory()->create(['title' => 'Old title', 'slug' => null]);

        $task->update(['title' => 'Brand new title']);

        $this->assertSame('brand-new-title', $task->fresh()->slug);
    }

    public function test_an_unchanged_title_keeps_the_slug(): void
    {
        $task = Task::factory()->create(['title' => 'Stable', 'slug' => null]);

        $task->update(['status' => 'done']);

        $this->assertSame('stable', $task->fresh()->slug);
    }

    public function test_deleting_a_task_deletes_its_comments_and_attachments(): void
    {
        $task = Task::factory()->withComments(2)->create();
        $task->attachments()->save(Attachment::factory()->make());

        $task->delete();

        $this->assertSame(0, Comment::count());
        $this->assertSame(0, Attachment::count());
    }
}
