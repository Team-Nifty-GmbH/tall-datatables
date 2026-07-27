<?php

/**
 * Browser Tests for Reset Layout
 *
 * The reset only shows up in a real browser: the server side of resetLayout()
 * has always worked, what broke was the sidebar keeping its own copy of the
 * columns and never picking the reset ones up.
 */

use Tests\Fixtures\Livewire\DefaultColumnsPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'reset-layout@example.com']);

    createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Post Title 1',
        'content' => 'Content 1',
        'is_published' => true,
    ]);
});

describe('Reset layout', function (): void {
    it('pulls the reset columns back into the sidebar', function (): void {
        $page = visitLivewire(DefaultColumnsPostDataTable::class);

        $page->wait(2);

        $result = $page->script('async () => {
            const el = document.querySelector(\'[x-data^="datatableOptions"]\');

            if (! el) {
                return "no-options-component";
            }

            const options = window.Alpine.$data(el);
            const before = [...options.enabledCols];

            options.enabledCols = [before[0]];
            options.syncAvailableCols();

            await options.resetLayout();
            await new Promise((resolve) => setTimeout(resolve, 1500));

            return JSON.stringify({
                enabledCols: options.enabledCols,
                availableCols: options.availableCols,
                before,
            });
        }');

        $raw = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($raw)->not->toBe('no-options-component');

        $state = json_decode((string) $raw, true);

        expect($state['enabledCols'])->toBe($state['before'])
            ->and($state['availableCols'])->toBe([...$state['before'], '__placeholder__']);
    });
});
