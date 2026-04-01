<?php

/**
 * Browser Tests for Unified Filter System and Multi-Row OR Filters.
 *
 * Tests UI rendering, Alpine/Livewire integration, and JS error-free operation.
 * Filter logic (AND/OR, date parsing, group management) is covered by Feature tests.
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Filter User', 'email' => 'filter@example.com']);

    for ($i = 1; $i <= 15; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => $i <= 5 ? "Alpha Post {$i}" : "Beta Post {$i}",
            'content' => "Content {$i}",
            'is_published' => $i % 2 === 0,
        ]);
    }
});

describe('Text Filter UI', function (): void {
    it('applies text filter and reduces visible results', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(3)
            ->assertSee('Alpha Post 1')
            ->assertSee('Beta Post 6');

        // Apply filter via Livewire call (same function the x-on:input handler calls)
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Alpha", 0, 0);
        }');

        // Poll until filtered data loads (Beta posts disappear)
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) return resolve(false);
                    if (!document.body.textContent.includes("Beta Post 6")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->assertSee('Alpha Post 1')
            ->assertDontSee('Beta Post 6');
    });

    it('shows filter badge after applying text filter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(3);

        // Apply filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "xyz", 0, 0);
        }');

        // Wait for the filter to take effect and badge to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    // Server-rendered head badges strip % wildcards, so badge shows "xyz"
                    if (uf.length > 0 && document.body.textContent.includes("xyz")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $badgeFound = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($badgeFound)->toBeTrue('Expected filter badge containing "xyz" to appear');
    });

    it('has no javascript errors during filter operations', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "test", 0, 0);
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });

    it('stores text filter with source=text in userFilters', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(3);

        // Apply filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Alpha", 0, 0);
        }');

        // Poll until userFilters is populated
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const comp = document.querySelector("[wire\\\\:id]");
                        const wireId = comp?.getAttribute("wire:id");
                        const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                        return resolve({
                            groups: uf.length,
                            source: uf[0]?.[0]?.source ?? null,
                            column: uf[0]?.[0]?.column ?? null,
                        });
                    }
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) {
                        return resolve({
                            groups: uf.length,
                            source: uf[0]?.[0]?.source ?? null,
                            column: uf[0]?.[0]?.column ?? null,
                        });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['groups'])->toBe(1);
        expect($data['source'])->toBe('text');
        expect($data['column'])->toBe('title');
    });
});

describe('Multi-Row Filter Rows', function (): void {
    it('renders add-row button with correct title', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->assertPresent('button[title]');

        $result = $page->script('() => {
            const btn = document.querySelector("button[title]");
            return btn?.getAttribute("title") ?? null;
        }');
        $title = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($title)->toContain('OR');
    });

    it('adds a second filter row when clicking add button', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $before = $page->script('() => document.querySelectorAll("thead tr").length');
        $before = is_array($before) && isset($before[0]) ? $before[0] : $before;

        $page->script('() => {
            const wrapper = document.querySelector("[tall-datatable]");
            if (wrapper) Alpine.$data(wrapper).addTextFilterRow();
        }');

        $page->wait(1);

        $after = $page->script('() => document.querySelectorAll("thead tr").length');
        $after = is_array($after) && isset($after[0]) ? $after[0] : $after;

        expect($after)->toBe($before + 1);
    });

    it('shows or label in second filter row', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const wrapper = document.querySelector("[tall-datatable]");
            if (wrapper) Alpine.$data(wrapper).addTextFilterRow();
        }');

        $page->wait(1);

        $result = $page->script('() => {
            const rows = document.querySelectorAll("thead tr");
            return rows[2]?.querySelector("td")?.textContent?.trim() ?? null;
        }');
        $text = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($text)->toContain('or');
    });

    it('removes second filter row when x is clicked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Add second row
        $page->script('() => {
            const wrapper = document.querySelector("[tall-datatable]");
            if (wrapper) Alpine.$data(wrapper).addTextFilterRow();
        }');
        $page->wait(1);

        $before = $page->script('() => document.querySelectorAll("thead tr").length');
        $before = is_array($before) && isset($before[0]) ? $before[0] : $before;
        expect($before)->toBe(3);

        // Click x on second row
        $page->script('() => {
            const rows = document.querySelectorAll("thead tr");
            const btn = rows[2]?.querySelector("td:last-child button");
            if (btn) btn.click();
        }');
        $page->wait(2);

        $after = $page->script('() => document.querySelectorAll("thead tr").length');
        $after = is_array($after) && isset($after[0]) ? $after[0] : $after;

        expect($after)->toBe(2);
    });

    it('has no javascript errors with multiple filter rows', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Add row, type in both, remove row
        $page->script('() => {
            const wrapper = document.querySelector("[tall-datatable]");
            if (wrapper) Alpine.$data(wrapper).addTextFilterRow();
        }');
        $page->wait(1);

        // Apply filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Alpha", 0, 0);
        }');
        $page->wait(1);

        $page->assertNoJavascriptErrors();
    });
});
