<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataTable Browser Test</title>
    {!! str_replace(' defer', '', app(\TallStackUi\Foundation\Support\Blade\Directives::class)->setup()) !!}
    @dataTableStyles
    @dataTablesScripts
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
