<?php

namespace App\Domain\Task\Scopes;

use App\Domain\Task\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<Task>
 */
class ExcludeArchivedProjectTasksScope implements Scope
{
    /**
     * @param  Builder<covariant Task>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('project', function (Builder $query) {
            $query->whereNull('archived_at');
        })->orWhereNull('project_id');
    }
}
