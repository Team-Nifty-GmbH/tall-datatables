<?php

/**
 * A bulk change broadcasts one event per model.
 *
 * The table called the component once per event, and Livewire sends every call
 * made while a request of the same component is in flight in one batch. A few
 * hundred models saved at once ran past the payload.max_calls limit and the
 * table failed with a server error.
 */

use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Livewire\BroadcastablePostDataTable;
use Tests\Fixtures\Models\BroadcastablePost;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'echo-burst@example.com']);
    $this->actingAs($this->user);

    $this->post = BroadcastablePost::withoutBroadcasting(fn () => BroadcastablePost::query()->create([
        'user_id' => $this->user->getKey(),
        'title' => 'Before Burst',
        'content' => 'Content',
        'is_published' => true,
    ]));
});

it('stays below the call limit when many events arrive at once', function (): void {
    $page = visitLivewire(BroadcastablePostDataTable::class);

    $page->wait(2);

    $page->script('() => {
        window.__echoCallbacks = [];
        window.Echo = {
            private() {
                return {
                    listenToAll(callback) {
                        window.__echoCallbacks.push(callback);

                        return this;
                    },
                    listen(event, callback) {
                        window.__echoCallbacks.push((name, data) => name === event && callback(data));

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
    }');

    DB::table('posts')->where('id', $this->post->getKey())->update(['title' => 'After Burst']);

    $result = $page->script('() => {
        return new Promise((resolve) => {
            const statuses = [];
            const originalFetch = window.fetch;
            window.fetch = async (...args) => {
                const response = await originalFetch(...args);
                statuses.push(response.status);

                return response;
            };

            const callback = window.__echoCallbacks[0];
            for (let i = 1; i < 80; i++) {
                callback(".BroadcastablePostUpdated", { model: { id: 1000 + i } });
            }
            callback(".BroadcastablePostUpdated", { model: { id: ' . $this->post->getKey() . ' } });

            const start = Date.now();
            const check = () => {
                const text = document.querySelector("tbody").innerText;
                if ((text.includes("After Burst") && statuses.length) || Date.now() - start > 10000) {
                    return resolve({ text, statuses });
                }

                setTimeout(check, 100);
            };
            check();
        });
    }');

    expect($result['statuses'])->each->toBe(200)
        ->and($result['text'])->toContain('After Burst');
});
