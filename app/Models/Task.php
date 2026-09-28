<?php

namespace App\Models;

use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([TaskObserver::class])]
#[Fillable(['title', 'description', 'status', 'slug'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;
}
