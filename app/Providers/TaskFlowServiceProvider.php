<?php

namespace App\Providers;

use App\Domain\Notification\EmailChannel;
use App\Domain\Notification\NotificationChannel;
use App\Domain\Notification\SmsChannel;
use App\Domain\Notification\UrgentAlertService;
use App\Domain\Project\Models\Project;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Repositories\EloquentTaskRepository;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class TaskFlowServiceProvider extends ServiceProvider
{
    /**
     * Register TaskFlow's domain bindings.
     */
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);
        $this->app->bind(NotificationChannel::class, EmailChannel::class); // default for everyone

        $this->app->when(UrgentAlertService::class)
            ->needs(NotificationChannel::class)
            ->give(SmsChannel::class);
    }

    /**
     * Bootstrap TaskFlow's model conventions.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->environment('production'));

        Relation::enforceMorphMap([
            'task' => Task::class,
            'project' => Project::class,
        ]);

        // boot()-only: Blade's compiler isn't guaranteed ready during register().
        Blade::directive('priorityBadge', function (string $expression) {
            return "<?php echo '<span style=\"padding:2px 8px;border-radius:4px;background:' . ({$expression})->color() . '\">' . ({$expression})->label() . '</span>'; ?>";
        });
    }
}
