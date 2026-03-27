<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataTable Browser Test</title>
    @php
        $tallStackManifest = json_decode(file_get_contents(base_path('vendor/tallstackui/tallstackui/dist/build/manifest.json')), true);
        $tallStackJs = $tallStackManifest['resources/js/tallstackui.js']['file'] ?? null;
        $tallStackCss = collect($tallStackManifest)->filter(fn($v) => str_ends_with($v['file'] ?? '', '.css'))->pluck('file')->first();
    @endphp
    @if($tallStackJs)
        <script src="/tallstackui/script/{{ $tallStackJs }}"></script>
    @endif
    @if($tallStackCss)
        <link href="/tallstackui/style/{{ $tallStackCss }}" rel="stylesheet" type="text/css">
    @endif
    @dataTableStyles
    @dataTablesScripts
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
