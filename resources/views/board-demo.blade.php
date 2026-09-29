<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Board Demo</title>
</head>

<body>
    <h1>All Tasks</h1>

    <table border="1" cellpadding="6">
        <tr>
            <th>Title</th>
            <th>Project</th>
            <th>Assignee</th>
            <th>Comments</th>
        </tr>

        @foreach ($tasks as $task)
            <tr>
                <td>{{ $task->title }}</td>
                <td>{{ $task->project?->name }}</td>
                <td>{{ $task->assignee?->name }}</td>
                <td>{{ $task->comments_count }}</td>
            </tr>
        @endforeach
    </table>
</body>

</html>