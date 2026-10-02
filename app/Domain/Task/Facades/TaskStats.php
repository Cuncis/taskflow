<?php

namespace App\Domain\Task\Facades;

use App\Domain\Project\Models\Project;
use App\Domain\Task\TaskStats as TaskStatsService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static float completionRateFor(Project $project)
 * @method static int overdueCountFor(Project $project)
 *
 * @see TaskStatsService
 */
class TaskStats extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TaskStatsService::class;
    }
}
