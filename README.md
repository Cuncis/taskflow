# TaskFlow

A Kanban-style task management app, built as a learning project in senior-level Laravel architecture
(domain folders, Actions, repositories, events, Artisan commands).

## Requirements

- Docker and Docker Compose
- Git

You do **not** need PHP, MySQL, Redis or Node on your machine: everything runs in Docker through
[Laravel Sail](https://laravel.com/docs/sail). Free up ports 80, 3306, 6379 and 5173 first (or see
[Non-Obvious Things](#non-obvious-things-worth-knowing)).

## Setup

```bash
git clone https://github.com/Cuncis/taskflow.git
cd taskflow
cp .env.example .env
composer install --ignore-platform-reqs   # one-time: only fetches Sail itself (see note below)
./vendor/bin/sail up -d                   # first run builds the image, a few minutes
./vendor/bin/sail ps                      # wait until mysql shows (healthy), then continue
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
git config core.hooksPath .githooks       # turns on the pre-commit checks
```

Open <http://localhost>; <http://localhost/up> should return 200.

Optional, so the commands below work as written: `alias sail='./vendor/bin/sail'`.

> No PHP/Composer on your machine at all? Use Laravel's Docker-only bootstrap for the `composer install`
> step: <https://laravel.com/docs/sail#installing-composer-dependencies-for-existing-projects>

## Running Tests

```bash
sail artisan test
```

Tests must run through Sail: they use the `mysql` container's `testing` database, which your host can't reach.

With coverage:

```bash
docker compose exec -e XDEBUG_MODE=coverage laravel.test php artisan test --coverage
```

## Day-to-Day Commands

| Command | What it does |
|---|---|
| `sail up -d` / `sail down` | Start / stop the containers |
| `sail artisan migrate:fresh --seed` | Reset the database with fresh demo data |
| `sail composer check` | Everything CI would run: Pint, Larastan, tests |
| `sail pint` | Auto-fix code style |
| `sail bin phpstan analyse` | Static analysis (Larastan, level 8) |
| `sail artisan tinker` | Interactive REPL (PHP only: shell commands go in your shell, not here) |
| `sail artisan route:list --path=tasks` | Show the task routes |
| `sail artisan taskflow:archive-stale-tasks --dry-run` | Preview stale-task archiving without applying it |

## Architecture

Code is organised by business domain, not by file type. See [ARCHITECTURE.md](./ARCHITECTURE.md) for the folder
structure, how a request flows through it, and the reasoning behind the main decisions.

## API (current)

All routes need a logged-in user (unauthenticated JSON requests get `401`).

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/tasks` | Create a task |
| `PATCH` | `/tasks/{task}/assign` | Assign a task to a user (`assignee_id`) |
| `DELETE` | `/tasks/{task}/archive` | Archive a done task (`422` if it isn't done) |

## Non-Obvious Things Worth Knowing

- **Git hooks live in `.githooks/`, not `.git/hooks/`.** Run `git config core.hooksPath .githooks` once after
  cloning (it's in Setup), or the pre-commit checks won't run. The hook needs the containers up, because it runs the
  tests; `git commit --no-verify` skips it, for emergencies only.
- **Task queries can silently return fewer rows.** `ExcludeArchivedProjectTasksScope` hides tasks whose *project* is
  archived. Use `Task::withoutGlobalScopes()` to see everything. (A task's own `archived_at` is not filtered yet.)
- **`http://localhost` not loading although `sail up -d` succeeded?** Docker can restart without re-publishing the
  port. Check `docker port taskflow-laravel.test-1`; if it prints nothing, run `sail down && sail up -d`. If
  something else holds port 80, set `APP_PORT=8000` in `.env` and use `http://localhost:8000`.
- **`$task->update(['updated_at' => ...])` silently does nothing**: `updated_at` isn't mass-assignable. In tinker and
  tests use `forceFill()` (with `$task->timestamps = false`) to fake an old timestamp.
- **In `sail artisan tinker`, only PHP works.** Typing `sail artisan ...` there gives a confusing `PARSE ERROR`.
  Type `exit` to get your shell back.
- **Larastan is capped at level 8 on purpose**; the reason is in `phpstan.neon`.

## License

MIT.
