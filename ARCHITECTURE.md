# TaskFlow Architecture

## Folder Structure

TaskFlow organizes code by DOMAIN (business concept) rather than by TYPE (Controller,
Model, etc). Each domain folder under `app/Domain/` contains everything related to
that concept: its model, controllers, requests, events, listeners, observers, and
supporting services.

```
app/Domain/
├── Task/            models, Actions, repositories (interface, Eloquent, in-memory fake),
│                    exporters, events, listeners, observer, global scope, queued job,
│                    Console, Facades, http/{Controllers,Requests,Resources},
│                    TaskService (queries), TaskStats, SlugGenerator, TaskPriority, TaskStatus
├── Project/         Project model and Actions
├── Collaboration/   Comment and Attachment models
└── Notification/    channel abstraction (Email, SMS, Slack, Push), the factory that
                     selects one by user preference, and the services that use it
```

- `app/Domain/Task/` — tasks, the core unit of work in TaskFlow.
  - `Models/` — the Task Eloquent model. `status` is cast to the `TaskStatus` enum and
    `priority` to `TaskPriority`, so invalid values cannot be represented in code.
  - `Actions/` — single-purpose command classes: `CreateTaskAction`, `AssignTaskAction`,
    `MoveTaskAction`, `CompleteTaskAction`, `ArchiveTaskAction`, `UnarchiveTaskAction`.
    Each has one public `__invoke` and does exactly one thing, including enforcing its own
    business rule (only done tasks can be archived; only archived tasks can be unarchived).
    They are independently unit-testable.
  - `Events/` / `Listeners/` — `TaskCreated`, `TaskCompleted`, `TaskAssigned`, `TaskMoved`,
    `TaskArchived`, `TaskUnarchived` and their reactions (notifications, activity logging).
  - `Observers/` — `TaskObserver` handles model-integrity rules (slug generation, cascading
    comment/attachment deletes) that must hold however a Task is created or deleted.
  - `Repositories/` — `TaskRepositoryInterface` abstracts Eloquent-specific query logic.
  - `Http/` — controllers, form requests, API resources.
  - `Console/` — Artisan commands (`taskflow:archive-stale-tasks`), registered explicitly in
    `TaskFlowServiceProvider` and scheduled in `routes/console.php`, since they live outside
    Laravel's default scanned path.
  - `Scopes/` — the `ExcludeArchivedProjectTasksScope` global scope (query scopes live on the model).
  - `Facades/` — the `TaskStats` facade (see Facades below).
- `app/Domain/Project/` — projects, which group tasks together.
- `app/Domain/Collaboration/` — comments and attachments, which can belong to
  either a Task or a Project (polymorphic), so they don't belong to either
  domain exclusively.
- `app/Domain/Notification/` — the notification channel abstraction (Email,
  SMS, Slack, Push) and the factory that selects one based on user preference.

## How a request flows (start here)

"How does creating a task work?" in one trace, `POST /tasks`:

1. `routes/web.php` sends it (behind `auth`) to `TaskController@store`.
2. `StoreTaskRequest` validates the input (`status` must be a valid `TaskStatus`).
3. The controller calls `CreateTaskAction`, injected into the method. The action saves through
   `TaskRepositoryInterface` and dispatches `TaskCreated`.
4. On save, `TaskObserver` fills in the slug.
5. Listeners react to `TaskCreated`: a notification to the team, a queued log job, a stats log.
6. The controller returns a `TaskResource`.

The other endpoints follow the same shape: `POST /tasks/{task}/assign` (`AssignTaskAction`, then
`SendTaskAssignedNotification` notifies the assignee through `UserNotifier`, which picks the
channel from that user's own preference) and `DELETE /tasks/{task}/archive` (`ArchiveTaskAction`;
a business-rule failure becomes a 422, not a 500). Housekeeping runs from the scheduler, not from a
request: `taskflow:archive-stale-tasks` archives tasks done 90+ days ago.

## Commands vs Queries

Operations that change state, and usually fire events, are Actions (commands). Reads stay
as plain methods: `TaskService` now contains only queries, and `TaskStats` is a read-only
statistics service. The rule of thumb: if calling it twice could do something different or
side-effecty (create a row, fire an event, send a notification) it is a command and gets an
Action; if repeating it is always safe it is a query and a service or repository method is fine.

## Facades

TaskFlow has one internal Facade, `TaskStats`, built to understand the mechanism
(`getFacadeAccessor()` returns a container key; `__callStatic` forwards to that object).
In practice the codebase prefers constructor injection for its own domain services, because
the constructor is then an honest list of a class's dependencies (see
`GetProjectSummaryActionInjected`), and reserves facade-style calls for Laravel's own
framework concerns (Cache, Log, Http, Queue). Larastan can see through our facade because
its accessor is a real class name, so a mistyped facade method is still caught statically.

## Service Providers

TaskFlow-specific wiring lives in `TaskFlowServiceProvider`, kept separate from the
framework-generic `AppServiceProvider` (which is left empty): repository and notification
bindings, the `TaskStats` singleton, the morph map, the lazy-loading guard, Artisan command
registration and custom Blade directives. Providers are listed in `bootstrap/providers.php`.
`register()` only declares bindings; anything that resolves a binding belongs in `boot()`,
because provider order inside `register()` is not something to depend on.

## What's NOT domain-organized, on purpose

- `database/factories`, `database/migrations`, `database/seeders` stay in
  Laravel's conventional locations, since Artisan's generators and Eloquent's
  factory-discovery convention are tightly coupled to them.
- `app/Models/User.php` stays in its default location, since Laravel's
  authentication internals carry soft assumptions about this path.
- `app/Http/Controllers/Controller.php` (the base class) and
  `app/Providers/` stay where the framework expects them.

## Conventions that only work because of explicit wiring

Moving classes out of Laravel's default folders removes some conventions that
"just worked". These are the places where that wiring is now explicit; if you
add a new domain, do the same:

- **Model factories.** Eloquent guesses `Database\Factories\<Model>Factory` only for
  models in `App\Models`. Every domain model therefore declares
  `#[UseFactory(XFactory::class)]`, and every factory declares
  `protected $model = X::class`. A new model without both breaks `X::factory()`.
- **Event listeners.** Laravel only auto-discovers listeners in `app/Listeners`. Task's
  listeners are registered with `->withEvents(discover: [...])` in
  `bootstrap/app.php`. A listener placed in a new domain folder is silently never
  called until that folder is added there. Run `sail artisan event:list` to check.
  (If events are cached in production, re-run `event:cache` after changes.)
- **Morph map.** `Relation::enforceMorphMap([...])` in `TaskFlowServiceProvider` maps
  `task` and `project` to their model classes, so polymorphic rows in the database
  do not depend on class names or namespaces. Moving a model does not require a
  data migration.
- **Artisan commands.** Commands in `app/Domain/Task/Console` are registered with
  `$this->commands([...])` in `TaskFlowServiceProvider::boot()`.
- **Repository binding.** `TaskRepositoryInterface` is bound to
  `EloquentTaskRepository` in `TaskFlowServiceProvider`; tests can use
  `FakeTaskRepository` (in-memory) instead.

## Why this structure

The honest reason is a question a new developer will ask on day one: "how does
creating a task actually work?" Under Laravel's default layout the answer was spread
over nine top-level folders (Models, Observers, Events, Listeners, Jobs,
Http/Controllers, Http/Requests, Http/Resources, Domain/Task), and the only way to
find the pieces was to already know they existed. In the domain layout it is one
folder: open `app/Domain/Task/` and the request, the action, the observer that
writes the slug, the event, and the listeners it triggers are all within a few
subfolders of each other.

It also matches how the code actually changes. When we changed how tasks get their
`project_id`, or added the `Urgent` priority, the code edits landed inside
`app/Domain/Task/` (plus its tests and, for `Urgent`, the factory in
`database/factories`). Change-by-feature is the normal case; a
folder per file *type* means every feature touches every folder.

The costs are real, and worth stating so nobody is surprised by them:

- **Some judgment calls.** Comment and Attachment attach to either a Task or a
  Project, so putting them under either one would be arbitrary. They get their own
  small `Collaboration` domain. `User` stays in `app/Models` because Laravel's
  authentication assumes it. Not everything has an obvious home, and that is fine.
- **Laravel's conventions no longer do the work for us.** Factory lookup and
  listener discovery both assume the default folders, so they are wired up
  explicitly (see above). Forgetting one fails in a confusing way: a factory that
  can't be found, or a listener that is simply never called.
- **Namespaces must mirror folders exactly.** PSR-4 is unforgiving about this, and
  the failure is loud but indirect. A single wrong `namespace` line in
  `TaskObserver.php` did not produce an error in that file: composer warned it
  "does not comply with psr-4", the `Task` model then failed with "Unable to find
  observer", and the whole test suite died before reporting a single failure.
  If the tests suddenly stop running altogether after a move, check namespaces first
  (`sail composer dump-autoload -o` names the offending file).

We think that trade is worth it at this size and will keep paying off as more
features arrive. It is a folder convention, not a framework: nothing here stops
a domain from using another domain's classes, and we do not try to enforce
boundaries between them.

## Things that will surprise you

- **Archived tasks are not hidden yet.** `ExcludeArchivedProjectTasksScope` only hides tasks whose
  *project* is archived. `tasks.archived_at` (set by `ArchiveTaskAction`) is not filtered by any scope,
  so an archived task still shows up in normal queries until someone adds that.
- **`TaskExporterInterface` has no container binding, on purpose.** CSV, JSON, PDF and XML are all valid,
  so there is no single default to bind. Exporters are built with `new` and passed in.
- **`TaskStatus` and `TaskPriority` are enum casts.** `$task->status` is an enum, not a string: compare
  with `TaskStatus::Done`, and use `->value` when you need the string. The `casts()` docblock on `Task`
  must use string-literal class names, or Larastan falls back to treating the column as a string.
- **Mass assignment is restrictive.** `Task`'s `#[Fillable]` list omits `updated_at`, so
  `$task->update(['updated_at' => ...])` silently does nothing. Use `forceFill()` when seeding timestamps
  in tests or tinker.
- **`FakeTaskRepository`** is an in-memory test double, not dead code: unit tests use it to avoid the database.
- **`LifecycleTestController`** and the `/board-demo` and `/projects-demo` routes are leftover learning demos.

## Quality checks

`sail composer check` runs Pint (style), PHPStan/Larastan (static analysis, level 7)
and the test suite. Tests use a separate `testing` MySQL database and never write to
the real log, send HTTP, or run real queued jobs (see `phpunit.xml` and
`tests/TestCase.php`). Run it inside Sail: on the host, the `mysql` hostname does not
resolve.
