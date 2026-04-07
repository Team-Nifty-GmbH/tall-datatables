<?php

/**
 * Browser Tests for Relation Count Columns
 *
 * Tests that:
 * 1. The relation count checkbox enables the column in enabledCols
 * 2. Count column labels use separately translated parts
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->actingAs($this->user);

    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post With Comments']);
    createTestComment(['user_id' => $this->user->getKey(), 'post_id' => $post->getKey()]);
    createTestComment(['user_id' => $this->user->getKey(), 'post_id' => $post->getKey()]);

    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post Without Comments']);
});

describe('Relation Count Column Checkbox', function (): void {
    it('adds count column to enabledCols when relation count checkbox is checked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Open the sidebar
        $page->script('() => {
            const buttons = document.querySelectorAll("button");
            for (const btn of buttons) {
                const onclick = btn.getAttribute("x-on:click") || "";
                if (onclick.includes("open.slide") && btn.closest("th")) {
                    btn.click();
                    break;
                }
            }
        }');

        $page->wait(1);

        // Click the Columns tab
        $page->script('() => {
            const tabs = document.querySelectorAll("button, [role=tab]");
            for (const tab of tabs) {
                if (tab.textContent.trim() === "Columns") {
                    tab.click();
                    break;
                }
            }
        }');

        $page->wait(1);

        // Find and click the comments_count checkbox in the Relations section
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const checkboxes = Array.from(document.querySelectorAll("input[type=checkbox]"))
                            .map(cb => ({ value: cb.value, checked: cb.checked }));
                        return resolve({ timeout: true, checkboxes });
                    }
                    const checkboxes = document.querySelectorAll("input[type=checkbox]");
                    for (const cb of checkboxes) {
                        if (cb.value === "comments_count") {
                            cb.click();
                            return resolve({ timeout: false, found: true });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        if ($data['timeout']) {
            $page->screenshot(true, 'count-checkbox-timeout');
        }

        expect($data['timeout'])->toBeFalse(
            'Could not find comments_count checkbox. Found: ' . json_encode($data['checkboxes'] ?? [])
        );

        $page->wait(1);

        // Verify that comments_count is now in enabledCols on the Livewire component
        $enabledResult = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 5000) {
                        const comp = document.querySelector("[wire\\\\:id]");
                        const wireId = comp?.getAttribute("wire:id");
                        const cols = window.Livewire?.find(wireId)?.$get("enabledCols") ?? [];
                        return resolve({ timeout: true, enabledCols: cols });
                    }
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const cols = window.Livewire?.find(wireId)?.$get("enabledCols") ?? [];
                    if (cols.includes("comments_count")) {
                        return resolve({ timeout: false, enabledCols: cols });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $enabledData = is_array($enabledResult) && isset($enabledResult[0]) ? $enabledResult[0] : $enabledResult;
        expect($enabledData['timeout'])->toBeFalse(
            'comments_count was not added to enabledCols. Current: ' . json_encode($enabledData['enabledCols'])
        );
        expect($enabledData['enabledCols'])->toContain('comments_count');
    });
});

describe('Relation Count Column Labels', function (): void {
    it('uses separately translated label parts for count columns', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable the comments_count column via Livewire
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("storeColLayout", ["title", "comments_count"]);
        }');

        // Wait for reload and check the table header label
        $labelResult = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const headers = Array.from(document.querySelectorAll("th"))
                            .map(th => th.textContent.trim())
                            .filter(t => t.length > 0);
                        return resolve({ timeout: true, headers });
                    }
                    const headers = Array.from(document.querySelectorAll("th"))
                        .map(th => th.textContent.trim())
                        .filter(t => t.length > 0);
                    const countHeader = headers.find(h => h.toLowerCase().includes("comment"));
                    if (countHeader) {
                        return resolve({ timeout: false, countHeader, headers });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $labelData = is_array($labelResult) && isset($labelResult[0]) ? $labelResult[0] : $labelResult;

        if ($labelData['timeout']) {
            $page->screenshot(true, 'count-label-timeout');
        }

        expect($labelData['timeout'])->toBeFalse(
            'Timed out waiting for count column header. Headers: ' . json_encode($labelData['headers'] ?? [])
        );

        // The label should NOT be "Comments Count" (single Str::headline translation)
        // It should use separately translated parts: __('Comments') . ' ' . __('count')
        expect($labelData['countHeader'])->not->toBe('Comments Count');
    });
});

describe('Nested Relation Count Checkbox', function (): void {
    it('does not show count checkboxes for nested relations', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Open the sidebar
        $page->script('() => {
            const buttons = document.querySelectorAll("button");
            for (const btn of buttons) {
                const onclick = btn.getAttribute("x-on:click") || "";
                if (onclick.includes("open.slide") && btn.closest("th")) {
                    btn.click();
                    break;
                }
            }
        }');

        $page->wait(1);

        // Click the Columns tab
        $page->script('() => {
            const tabs = document.querySelectorAll("button, [role=tab]");
            for (const tab of tabs) {
                if (tab.textContent.trim() === "Columns") {
                    tab.click();
                    break;
                }
            }
        }');

        $page->wait(1);

        // Navigate into the "user" relation (click the relation row, not its checkbox)
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    // Find the relation row with "User" text and click it to navigate
                    const spans = document.querySelectorAll("span");
                    for (const span of spans) {
                        if (span.textContent.trim() === "User") {
                            // Click the parent div (the relation row), not the checkbox
                            const row = span.closest("div.flex.cursor-pointer");
                            if (row) {
                                row.click();
                                return resolve({ timeout: false });
                            }
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->wait(2);

        // Now we are inside user relations. Count checkboxes should NOT be visible.
        $result = $page->script('() => {
            const checkboxes = Array.from(document.querySelectorAll("input[type=checkbox]"))
                .filter(cb => {
                    if (!cb.value.endsWith("_count")) return false;
                    const wrapper = cb.closest("[x-show]");
                    if (wrapper && wrapper.offsetParent === null) return false;
                    return cb.offsetParent !== null;
                })
                .map(cb => cb.value);
            return { countCheckboxes: checkboxes };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        if (! empty($data['countCheckboxes'])) {
            $page->screenshot(true, 'nested-count-checkboxes-visible');
        }

        expect($data['countCheckboxes'])->toBeEmpty(
            'Count checkboxes should not appear for nested relations. Found: ' . json_encode($data['countCheckboxes'])
        );
    });
});
