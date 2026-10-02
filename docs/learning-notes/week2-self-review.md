# Week 2 Self-Review

Reviewed in priority order: correctness, security, maintainability, test coverage, intent.
Severity labels: **Approve**, **Non-blocking**, **Request changes**.
Everything labelled *Request changes* was fixed in the same pass; one *Non-blocking* item was fixed too.

| # | File | Verdict | Status |
|---|------|---------|--------|
| 1 | `Task/Export/*` (CSV, JSON, PDF, XML) | Request changes | Fixed (CSV) |
| 2 | `Task/SlugGenerator.php` + `EloquentTaskRepository::slugExists` | Request changes | Fixed |
| 3 | `Notification/NotificationChannelFactory.php` | Approve | n/a |
| 4 | `Task/Repositories/EloquentTaskRepository.php` | Non-blocking | Duplication fixed, rest noted |
| 5 | `Task/Listeners/*` | Non-blocking | Noted |
| 6 | `Task/Models/Task.php` | Approve | n/a |
| 7 | `TaskController@archive` (this week's newest code) | Non-blocking | Noted |

---

## 1. Exporters (`app/Domain/Task/Export/`)

**Strength.** The `canExport()` guard is handled consistently: every exporter re-checks it inside
`export()` and throws, and `MultiFormatTaskExporter` checks it before calling and degrades to a
"Skipped" message. There is no call site anywhere that calls `export()` unguarded, so the
contract in `TaskExporterInterface` is actually honoured, not just declared.

**Issue (Request changes, fixed).** `CsvTaskExporter` built rows with
`"{$task['title']},{$task['status']}"`. A title like `Fix login, then deploy` or one containing a
quote or newline silently produces a row with the wrong number of columns, so anyone opening the file
gets shifted data with no error. The header was also `title, status` (with a space) while the rows had
none, so the header's second column name was `" status"`. I'd want this fixed before anything real
consumes CSV, because the corruption is silent. Fix: build the output with `fputcsv`, which quotes
correctly, and the header is now `title,status`. A test covers a title with a comma, quotes and a newline.

Not fixed, worth knowing: a title starting with `=`, `+`, `-` or `@` can be interpreted as a formula when
the CSV is opened in a spreadsheet. Nothing exports user data yet, so this is for when the export is
wired to a real endpoint.

## 2. Slug uniqueness (`SlugGenerator` + `EloquentTaskRepository::slugExists`)

**Strength.** The observer's rules are carefully scoped: only a changed title regenerates the slug, and
a slug set explicitly in the same save is left alone. Small, clear, and tested.

**Issue (Request changes, fixed).** This one was found by checking whether `slugExists()` was ever
called. It wasn't: `SlugGenerator` ran its own `Task::where('slug', ...)` query. That query goes through
`ExcludeArchivedProjectTasksScope`, so tasks in archived projects are invisible to it, but `tasks.slug` has a
unique index. Creating a task titled "Write docs" when a "Write docs" task exists in an archived project
generated the same slug and failed with an integrity-constraint error, which a user would see as a 500.
Reproduced with a test before fixing. Fix: `SlugGenerator` now asks `TaskRepositoryInterface::slugExists()`
(so the duplicated query is gone), and the Eloquent implementation uses `withoutGlobalScopes()`, with a
comment saying why. A regression test covers it.

## 3. `NotificationChannelFactory`

**Strength.** The `match` has one arm per `NotificationPreference` case and no `default`. That is the right
call: adding a fifth case makes it throw `UnhandledMatchError` instead of quietly picking a wrong channel,
and Larastan flags it statically first.

**Question.** It takes the whole `Container` rather than the four channels. That is a service-locator
shape, but for a factory whose job is "resolve one of N by key" it is the honest tool, and it keeps channel
construction lazy. Fine as is. **Approve.** Single purpose, nothing has crept in.

## 4. `EloquentTaskRepository`

**Strength.** `findOverdue()` still goes through `Task::overdue()`, so the definition of "overdue" lives in
one place (the scope) and did not drift when `status` became an enum.

**Issue (Non-blocking).** The "not done" rule (`status != done`) was written out five times: the model's
`overdue` and `dueSoon` scopes, `findActiveForProject`, and twice in `FakeTaskRepository`. When `status`
became an enum every copy had to be edited by hand, which is how rules drift. I added a `Task::active()`
scope and the real code now uses it (the fake still repeats it by hand, since it has no query builder).
Still noted, not done: `findOverdue()` has no callers outside the interface and its two implementations,
and `find()` / `update()` are pure pass-throughs. Per the Day 10 test they are ceremony today; I would
delete `findOverdue()` unless a caller shows up soon.

## 5. Listeners (`app/Domain/Task/Listeners/`)

**Strength.** `SendTaskAssignedNotification` is exactly one responsibility in three lines: it builds the
message and hands it to `UserNotifier`, which owns the "which channel" decision. It is also tested
end to end with only the final channel swapped.

**Issue (Non-blocking).** `UpdateProjectStatistics` does not update any statistics; it writes a log line.
Its name promises behaviour it does not have, and someone will eventually trust it. Either rename it
(`LogProjectStatisticsUpdate`) or add a comment saying it is a placeholder. Smaller: `SendTaskCreatedNotification`
hardcodes `team@taskflow.test`, which belongs in config. Neither listener does two things, so no
single-responsibility problem; this is about honest names and hardcoded values.

## 6. `Task` model

**Strength.** Business rules did not leak into it. "Only done tasks can be archived" lives in
`ArchiveTaskAction`, not in a model event or accessor, so the model is still mostly structure.

**Count.** Five relationships, five scopes plus one global scope, four casts, one observer. Reasonable. The
thing to watch is the scope list: it grows one method per query a screen needs. If it passes about ten,
split query-only scopes into a dedicated builder. **Approve**, with that as a standing note.

## 7. `TaskController@archive` (this week's newest code)

**Strength.** The business-rule failure becomes a 422 with the rule's own message, and there is a test
proving it is not a 500.

**Issue (Non-blocking).** It catches `RuntimeException`, which is broad: an unrelated `RuntimeException` from
somewhere inside the action would also be reported to the client as "422 your task isn't archivable".
A dedicated exception thrown by the action would make the catch say what it means.

## Reflection

*This section is my reflection as the reviewer of this pass, not yours. Add your own paragraph below.*

I noticed the **lenient** failure mode, not the harsh one. Two examples from this very pass: the CSV test I
wrote earlier asserted the output `title, status\nFirst,todo...`, so my own test had cemented the odd header
as "expected" and made the bug look verified. And I had already flagged the broad `RuntimeException` catch as
a caveat when I wrote it, then filed it as a footnote instead of a finding. Remembering *why* I wrote
something made it easy to explain away. The most serious bug (the slug collision) surfaced only because I
checked who actually calls `slugExists()`, rather than reading the code and trusting that it was used.
The check that works is mechanical: grep for callers and try to break the thing, rather than re-reading it
and agreeing with myself.

**Your reflection:** _(write yours here: which failure mode did you notice, if either?)_
