# Performance Notes

A running log of query-count problems found and fixed. One entry per fix: page, before, after, what fixed it.

## 2026-09-29: `/projects-demo`

- **Page:** `/projects-demo` (every project, its tasks, each task's assignee name)
- **Before:** 61 queries (1 projects + 20 tasks, one per project + 40 users, one per assigned task), measured on 20 projects and 52 tasks. It grows with the data.
- **After:** 3 queries (projects, tasks `IN (...)`, users `IN (...)`), however many rows there are.
- **Fix:** nested eager loading, `Project::with('tasks.assignee')->get()`, instead of `Project::all()` with lazy loads in the loops.
- **Guardrail:** `Model::preventLazyLoading()` is on outside production (`AppServiceProvider`). It made the naive page throw, so the N+1 showed up straight away in development.

### Related measurements (same day)

Counting comments for 10 tasks:

| Approach | Queries |
|---|---|
| `$task->comments->count()` in a loop (lazy load) | 11 |
| `$task->comments()->count()` in a loop | 11 |
| `Task::with('comments')`, then `->comments->count()` | 2 |
| `Task::withCount('comments')` | 1 |

Column selection: `Task::with('project:id,name')->select(['id', 'title'])` returns `project = null` for every task, because the foreign key `project_id` was not selected, so Laravel cannot match tasks to projects. Adding `project_id` to `select()` fixes it. When constraining columns on an eager load, always include the foreign key (and the related model's key) in both places.
