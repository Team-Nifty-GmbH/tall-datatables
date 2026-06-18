<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>DataTable Echo Browser Test</title>
        <script>
            window.__echoChannels = [];
            window.__lwBroadcastCalls = [];

            window.Echo = {
                socketId() {
                    return '1234.5678';
                },
                private(channel) {
                    window.__echoChannels.push(channel);

                    return {
                        listenToAll() {
                            return this;
                        },
                        listen() {
                            return this;
                        },
                    };
                },
                leave(channel) {
                    window.__echoChannels = window.__echoChannels.filter(
                        (ch) => ch !== channel,
                    );
                },
            };

            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                const body = args[1]?.body;
                if (
                    typeof body === 'string' &&
                    body.includes('broadcastChannels')
                ) {
                    window.__lwBroadcastCalls.push(body);
                }

                return originalFetch.apply(this, args);
            };
        </script>
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
