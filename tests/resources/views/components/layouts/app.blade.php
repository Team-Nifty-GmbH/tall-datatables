<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataTable Browser Test</title>
    @php
        $tsRoot = dirname((new ReflectionClass(\TallStackUi\TallStackUiServiceProvider::class))->getFileName(), 2);
        $tsManifestPath = $tsRoot . '/dist/.vite/manifest.json';
        $tsManifest = file_exists($tsManifestPath) ? json_decode(file_get_contents($tsManifestPath), true) : [];
        $tsJs = $tsManifest['js/tallstackui.js']['file'] ?? null;
        $tsCss = collect($tsManifest)->map(fn($v) => $v['file'] ?? null)->filter(fn($f) => $f && str_ends_with($f, '.css'));
    @endphp
    @foreach($tsCss as $css)
        <link href="/tallstackui/style/{{ $css }}" rel="stylesheet" type="text/css">
    @endforeach
    @if($tsJs)
        <script src="/tallstackui/script/{{ $tsJs }}"></script>
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
