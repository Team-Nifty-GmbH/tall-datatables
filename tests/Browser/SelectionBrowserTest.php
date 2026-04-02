<?php

/**
 * Browser Tests for DataTable Selection
 *
 * Tests checkbox selection, select all/wildcard, and deselection
 * using PostDataTable which has isSelectable = true.
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Selection User', 'email' => 'selection@example.com']);

    for ($i = 1; $i <= 10; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Selectable Post {$i}",
            'content' => "Content for selectable post {$i}",
            'is_published' => $i % 2 === 0,
        ]);
    }
});

describe('DataTable Selection', function (): void {
    it('has isSelectable property set to true', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("isSelectable") ?? null;
        }');

        $isSelectable = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($isSelectable)->toBeTrue();
    });

    it('starts with empty selected array', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("selected") ?? null;
        }');

        $selected = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($selected)->toBeArray();
        expect($selected)->toBeEmpty();
    });

    it('selects a single row via toggleSelected', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Get the first row ID from DOM
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const row = document.querySelector("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (row) {
                        const key = row.getAttribute("wire:key");
                        const id = parseInt(key.replace("row-", ""));
                        return resolve({ timeout: false, id });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $rowData = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($rowData['timeout'])->toBeFalse('Timed out waiting for rows to render');

        $rowId = $rowData['id'];

        // Select via Livewire call
        $page->script("() => {
            const comp = document.querySelector('[wire\\\\:id]');
            const wireId = comp?.getAttribute('wire:id');
            window.Livewire?.find(wireId)?.call('toggleSelected', {$rowId});
        }");

        // Wait for selected to update
        $result = $page->script("() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, selected: [] });
                    const comp = document.querySelector('[wire\\\\:id]');
                    const wireId = comp?.getAttribute('wire:id');
                    const selected = window.Livewire?.find(wireId)?.\$get('selected') ?? [];
                    if (selected.includes({$rowId})) return resolve({ timeout: false, selected });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }");

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for selection');
        expect($data['selected'])->toContain($rowId);
    });

    it('deselects a row via toggleSelected', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Get first row ID
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const row = document.querySelector("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (row) {
                        const key = row.getAttribute("wire:key");
                        return resolve({ timeout: false, id: parseInt(key.replace("row-", "")) });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $rowData = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($rowData['timeout'])->toBeFalse();
        $rowId = $rowData['id'];

        // Select the row
        $page->script("() => {
            const comp = document.querySelector('[wire\\\\:id]');
            const wireId = comp?.getAttribute('wire:id');
            window.Livewire?.find(wireId)?.call('toggleSelected', {$rowId});
        }");

        // Wait for selection
        $page->script("() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector('[wire\\\\:id]');
                    const wireId = comp?.getAttribute('wire:id');
                    const selected = window.Livewire?.find(wireId)?.\$get('selected') ?? [];
                    if (selected.includes({$rowId})) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }");

        // Deselect
        $page->script("() => {
            const comp = document.querySelector('[wire\\\\:id]');
            const wireId = comp?.getAttribute('wire:id');
            window.Livewire?.find(wireId)?.call('toggleSelected', {$rowId});
        }");

        // Wait for deselection
        $result = $page->script("() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, selected: [] });
                    const comp = document.querySelector('[wire\\\\:id]');
                    const wireId = comp?.getAttribute('wire:id');
                    const selected = window.Livewire?.find(wireId)?.\$get('selected') ?? [];
                    if (!selected.includes({$rowId})) return resolve({ timeout: false, selected });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }");

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for deselection');
        expect($data['selected'])->not->toContain($rowId);
    });

    it('selects all via wildcard by setting selected with asterisk', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Wait for rows to load
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const rows = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (rows.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Set selected to include wildcard via $wire.$set
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                // Get row IDs from the DOM and add wildcard
                const rows = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");
                const ids = Array.from(rows).map(r => parseInt(r.getAttribute("wire:key").replace("row-", "")));
                ids.push("*");
                lw.$set("selected", ids);
            }
        }');

        // Wait for wildcard to be set
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, selected: [] });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const selected = window.Livewire?.find(wireId)?.$get("selected") ?? [];
                    if (selected.includes("*")) return resolve({ timeout: false, selected, hasWildcard: true });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for wildcard selection');
        expect($data['hasWildcard'])->toBeTrue();
    });

    it('clears selection by setting selected to empty array', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // First select something
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const row = document.querySelector("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (row) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const row = document.querySelector("tbody tr[wire\\\\:key^=\\"row-\\"]");
            const id = parseInt(row.getAttribute("wire:key").replace("row-", ""));
            window.Livewire?.find(wireId)?.call("toggleSelected", id);
        }');

        // Wait for selection
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const selected = window.Livewire?.find(wireId)?.$get("selected") ?? [];
                    if (selected.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear all selection
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("selected", []);
        }');

        // Wait for selection to clear
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, count: -1 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const selected = window.Livewire?.find(wireId)?.$get("selected") ?? [];
                    if (selected.length === 0) return resolve({ timeout: false, count: 0 });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for selection to clear');
        expect($data['count'])->toBe(0);
    });

    it('has no javascript errors during selection operations', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Toggle selection on a row
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const row = document.querySelector("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (row) {
                        const id = parseInt(row.getAttribute("wire:key").replace("row-", ""));
                        const comp = document.querySelector("[wire\\\\:id]");
                        const wireId = comp?.getAttribute("wire:id");
                        window.Livewire?.find(wireId)?.call("toggleSelected", id);
                        return resolve(true);
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});
