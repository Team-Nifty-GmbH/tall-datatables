<?php

/**
 * Browser Tests for filter input sync when removing filter badges.
 *
 * When a user removes a filter via the badge X button, the corresponding
 * text input in the filter row must be cleared.
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Sync User', 'email' => 'sync@example.com']);

    for ($i = 1; $i <= 10; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => $i <= 5 ? "Alpha Post {$i}" : "Beta Post {$i}",
            'content' => "Content {$i}",
            'is_published' => $i % 2 === 0,
        ]);
    }
});

describe('Filter Input Sync on Badge Removal', function (): void {
    it('clears the text input when the filter badge is removed', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(3);

        // Type "Alpha" into the first text filter input (for the "title" column)
        // and trigger the Livewire setTextFilter call
        $page->script('() => {
            const inputs = document.querySelectorAll("thead input[type=search]");
            if (inputs.length > 0) {
                inputs[0].value = "Alpha";
                inputs[0].dispatchEvent(new Event("input", { bubbles: true }));
            }
        }');

        // Wait for the filter badge to appear
        $badgeAppeared = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $appeared = is_array($badgeAppeared) && isset($badgeAppeared[0]) ? $badgeAppeared[0] : $badgeAppeared;
        expect($appeared)->toBeTrue('Filter badge should appear after typing');

        // Verify the input still has the typed value
        $inputValue = $page->script('() => {
            const inputs = document.querySelectorAll("thead input[type=search]");
            return inputs[0]?.value ?? "";
        }');
        $val = is_array($inputValue) && isset($inputValue[0]) ? $inputValue[0] : $inputValue;
        expect($val)->toBe('Alpha', 'Input should retain typed value before badge removal');

        // Click the X button on the filter badge to remove the filter
        $page->script('() => {
            const badges = document.querySelectorAll("[wire\\\\:click^=\\"removeFilter\\"]");
            if (badges.length > 0) badges[0].click();
        }');

        // Wait for the filter to be removed (userFilters becomes empty)
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length === 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Now check that the text input has been cleared
        $clearedValue = $page->script('() => {
            const inputs = document.querySelectorAll("thead input[type=search]");
            return inputs[0]?.value ?? "NOT_FOUND";
        }');
        $cleared = is_array($clearedValue) && isset($clearedValue[0]) ? $clearedValue[0] : $clearedValue;
        expect($cleared)->toBe('', 'Text input should be cleared after removing filter badge');
    });

    it('clears the text input when clear all filters is clicked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(3);

        // Type "Beta" into the first text filter input
        $page->script('() => {
            const inputs = document.querySelectorAll("thead input[type=search]");
            if (inputs.length > 0) {
                inputs[0].value = "Beta";
                inputs[0].dispatchEvent(new Event("input", { bubbles: true }));
            }
        }');

        // Wait for the filter badge to appear
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Click the "Clear" button
        $page->script('() => {
            const buttons = document.querySelectorAll("[wire\\\\:click=\\"clearFiltersAndSort\\"]");
            if (buttons.length > 0) buttons[0].click();
        }');

        // Wait for filters to be cleared
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length === 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Check that the text input has been cleared
        $clearedValue = $page->script('() => {
            const inputs = document.querySelectorAll("thead input[type=search]");
            return inputs[0]?.value ?? "NOT_FOUND";
        }');
        $cleared = is_array($clearedValue) && isset($clearedValue[0]) ? $clearedValue[0] : $clearedValue;
        expect($cleared)->toBe('', 'Text input should be cleared after clicking Clear button');
    });
});
