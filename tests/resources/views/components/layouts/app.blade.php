<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataTable Browser Test</title>
    @tallStackUiStyle
    @dataTableStyles
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}

    @livewireScripts
    @tallStackUiScript
    @dataTablesScripts
</body>
</html>
