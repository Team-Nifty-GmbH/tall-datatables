<?php

/**
 * Browser Tests for the on-demand options sidebar
 *
 * Server side tests only prove whether the markup is sent. Whether the slide
 * actually opens, and still shows the server state on a second open, only
 * shows up in a real browser: the slide is teleported to <body> and lives
 * outside the component root, so a stale node would never surface in a
 * Livewire test.
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'sidebar-on-demand@example.com']);

    for ($i = 1; $i <= 3; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Post Title ' . $i,
            'content' => 'Content ' . $i,
            'is_published' => true,
        ]);
    }
});

describe('Options sidebar on demand', function (): void {
    it('is absent until the cog is clicked, then opens with its content', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Every script call is kept below a second: a slower one is retried by
        // the browser plugin, and a retry would click the opener twice.
        $before = $page->script('() => {
            const before = document.querySelectorAll(\'[x-data^="datatableOptions"]\').length;

            const button = [...document.querySelectorAll("[x-on\\\\:click]")]
                .find((el) => (el.getAttribute("x-on:click") || "").includes("showSidebar"));

            if (! button) {
                return "no-opener";
            }

            button.click();

            return before;
        }');

        $before = is_array($before) && isset($before[0]) ? $before[0] : $before;

        expect($before)->toBe(0);

        $page->wait(2.5);

        $result = $page->script('() => {
            const el = document.querySelector(\'[x-data^="datatableOptions"]\');

            if (! el) {
                return "no-options-component";
            }

            const options = window.Alpine.$data(el);

            return JSON.stringify({
                cols: options.enabledCols.length,
                // teleported: the slide is a direct child of body, not part of
                // the livewire root the button sits in
                outsideRoot: el.closest("[wire\\\\:id]") === null,
                visible: el.offsetParent !== null,
            });
        }');

        $raw = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($raw)->not->toBe('no-options-component');

        $state = json_decode((string) $raw, true);

        expect($state['cols'])->toBeGreaterThan(0)
            ->and($state['outsideRoot'])->toBeTrue()
            ->and($state['visible'])->toBeTrue();
    });

    it('shows the server state again after closing and reopening', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $clickOpener = '() => [...document.querySelectorAll("[x-on\\\\:click]")]
            .find((el) => (el.getAttribute("x-on:click") || "").includes("showSidebar"))
            .click()';

        // One click per script call, see above.
        $page->script($clickOpener);
        $page->wait(2.5);

        $closed = $page->script('() => {
            const closer = [...document.querySelectorAll("[x-on\\\\:click]")]
                .find((el) => (el.getAttribute("x-on:click") || "").includes("$tsui.close.slide"));

            if (! closer) {
                return "no-close-button";
            }

            closer.click();

            return "closed";
        }');

        $closed = is_array($closed) && isset($closed[0]) ? $closed[0] : $closed;

        expect($closed)->not->toBe('no-close-button');

        $page->wait(0.8);
        $page->script($clickOpener);
        $page->wait(1.5);

        $result = $page->script('() => {
            const wire = window.Livewire.all()[0].$wire;
            const el = document.querySelector(\'[x-data^="datatableOptions"]\');

            if (! el) {
                return "options-gone-after-reopen";
            }

            const options = window.Alpine.$data(el);

            return JSON.stringify({
                sidebar: [...options.enabledCols],
                server: [...wire.enabledCols],
                visible: el.offsetParent !== null,
            });
        }');

        $raw = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($raw)->not->toBe('options-gone-after-reopen');

        $state = json_decode((string) $raw, true);

        expect($state['sidebar'])->toBe($state['server'])
            ->and($state['visible'])->toBeTrue();
    });
});
