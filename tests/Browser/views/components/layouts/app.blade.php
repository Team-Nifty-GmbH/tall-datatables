<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link
            rel="shortcut icon"
            href="data:image/x-icon;,"
            type="image/x-icon"
        />
        <tallstackui:setup v4 />
        <x-toast />
        @dataTablesScripts
        @dataTableStyles
    </head>
    <body>
        <x-dialog />
        <x-toast />
        {!! $slot !!}
        <x-banner wire />
    </body>
</html>
