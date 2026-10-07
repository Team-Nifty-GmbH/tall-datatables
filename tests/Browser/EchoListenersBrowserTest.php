<?php

/**
 * The table subscribes the model events by name.
 *
 * Mercure channels in laravel-echo have no listenToAll(), so a table that
 * only listened to everything on a channel never refreshed under Mercure.
 * The fake Echo below has listen() alone, the way a Mercure channel does.
 */

use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Livewire\BroadcastablePostDataTable;
use Tests\Fixtures\Models\BroadcastablePost;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'echo-listeners@example.com']);
    $this->actingAs($this->user);

    $this->post = BroadcastablePost::query()->create([
        'user_id' => $this->user->getKey(),
        'title' => 'Before Echo',
        'content' => 'Content',
        'is_published' => true,
    ]);
});

it('listens to the model events by name and refreshes the row', function (): void {
    $page = visitLivewire(BroadcastablePostDataTable::class);

    $page->wait(2);

    $subscribed = $page->script('() => {
        window.__echoListeners = {};
        window.Echo = {
            private(channel) {
                return {
                    listen(event, callback) {
                        (window.__echoListeners[channel] ??= {})[event] = callback;

                        return this;
                    },
                };
            },
            leave() {},
            socketId() {
                return null;
            },
        };

        const el = document.querySelector("[tall-datatable]");
        window.Alpine.$data(el)._setupEchoListeners();

        return Object.fromEntries(
            Object.entries(window.__echoListeners).map(([channel, events]) => [channel, Object.keys(events)])
        );
    }');

    $channel = $this->post->broadcastChannel();

    expect($subscribed)->toHaveKey($channel)
        ->and($subscribed[$channel])->toContain(
            '.BroadcastablePostCreated',
            '.BroadcastablePostUpdated',
            '.BroadcastablePostDeleted',
            '.BroadcastablePostTrashed',
            '.BroadcastablePostRestored',
        );

    DB::table('posts')->where('id', $this->post->getKey())->update(['title' => 'After Echo']);

    $title = $page->script('() => {
        return new Promise((resolve) => {
            window.__echoListeners["' . $channel . '"][".BroadcastablePostUpdated"]({ model: { id: ' . $this->post->getKey() . ' } });

            const start = Date.now();
            const check = () => {
                const text = document.querySelector("tbody").innerText;
                if (text.includes("After Echo") || Date.now() - start > 10000) return resolve(text);

                setTimeout(check, 100);
            };
            check();
        });
    }');

    expect($title)->toContain('After Echo');
});
