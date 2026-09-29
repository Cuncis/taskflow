<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Projects Demo</title>
</head>

<body>
    <h1>Projects</h1>

    @foreach ($projects as $project)
        <h2>{{ $project->name }}</h2>

        <ul>
            @foreach ($project->tasks as $task)
                <li>{{ $task->title }} &mdash; {{ $task->assignee?->name ?? 'Unassigned' }}</li>
            @endforeach
        </ul>
    @endforeach
</body>

</html>
