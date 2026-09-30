<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeArchivedProjectTasksScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('project', function (Builder $query) {
            $query->whereNull('archived_at');
        })->orWhereNull('project_id');
    }
}
