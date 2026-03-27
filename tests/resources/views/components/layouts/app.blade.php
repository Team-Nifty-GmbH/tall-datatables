<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataTable Browser Test</title>
    <tallstackui:setup />
    @dataTableStyles
    @dataTablesScripts
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
