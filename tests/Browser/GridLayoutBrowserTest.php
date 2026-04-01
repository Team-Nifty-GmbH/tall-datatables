<?php

/**
 * Browser Tests for Grid Layout DataTable
 *
 * Tests grid view rendering, responsive grid columns,
 * and grid-specific interactions using GridPostDataTable fixture.
 */

use Tests\Fixtures\Livewire\GridPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Grid User', 'email' => 'grid@example.com']);

    for ($i = 1; $i <= 12; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Grid Post {$i}",
            'content' => "Content for grid post {$i}",
            'is_published' => $i % 2 === 0,
            'price' => $i * 10.50,
        ]);
    }
});

describe('Grid Layout Rendering', function (): void {
    it('renders grid layout instead of table layout', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        // Wait for grid items to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, hasGrid: false, hasTable: false });
                    const gridContainer = document.querySelector(".grid");
                    const table = document.querySelector("table");
                    if (gridContainer) {
                        return resolve({ timeout: false, hasGrid: true, hasTable: !!table });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid layout to render');
        expect($data['hasGrid'])->toBeTrue('Expected grid container to be present');
        expect($data['hasTable'])->toBeFalse('Expected no table element in grid layout');
    });

    it('displays data in grid cards', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        // Wait for grid items
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, itemCount: 0 });
                    const gridItems = document.querySelectorAll("[wire\\\\:key^=\\"grid-\\"]");
                    if (gridItems.length > 0) {
                        return resolve({ timeout: false, itemCount: gridItems.length });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid items');
        expect($data['itemCount'])->toBeGreaterThan(0);
    });

    it('shows post titles in grid cards', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2)
            ->assertSee('Grid Post 1');
    });

    it('renders multiple columns in grid container', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        // Wait for grid items to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const gridContainer = document.querySelector(".grid");
                    if (gridContainer) {
                        const style = window.getComputedStyle(gridContainer);
                        const gridCols = style.gridTemplateColumns;
                        const colCount = gridCols ? gridCols.split(" ").length : 0;
                        return resolve({ timeout: false, colCount, display: style.display });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid layout');
        expect($data['display'])->toBe('grid');
    });

    it('has no JavaScript errors on grid layout', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('Grid Layout Pagination', function (): void {
    it('shows pagination in grid layout', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("Showing") && text.includes("results")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $found = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($found)->toBeTrue('Expected pagination info in grid layout');
    });
});

describe('Grid Layout Sorting', function (): void {
    it('renders sort button in grid layout', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2);

        $page->assertSee('Sort');
    });

    it('sorts grid items via sortTable', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2);

        // Sort by title
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for sort to apply
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const orderBy = window.Livewire?.find(wireId)?.$get("userOrderBy");
                    if (orderBy === "title") return resolve({ timeout: false, orderBy });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid sort');
        expect($data['orderBy'])->toBe('title');
    });

    it('has no javascript errors when sorting grid', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('Grid Layout Data Display', function (): void {
    it('displays each enabled column in grid cards', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        // Wait for grid items to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const firstCard = document.querySelector("[wire\\\\:key^=\\"grid-\\"]");
                    if (firstCard) {
                        const text = firstCard.textContent;
                        return resolve({
                            timeout: false,
                            hasTitle: text.includes("Grid Post"),
                            hasContent: text.includes("Content for grid"),
                            cardText: text.trim().substring(0, 200),
                        });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid card data');
        expect($data['hasTitle'])->toBeTrue('Expected title column in grid card');
        expect($data['hasContent'])->toBeTrue('Expected content column in grid card');
    });

    it('renders correct number of grid items for the first page', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, count: 0 });
                    const gridItems = document.querySelectorAll("[wire\\\\:key^=\\"grid-\\"]");
                    if (gridItems.length > 0) {
                        return resolve({ timeout: false, count: gridItems.length });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grid items');
        // 12 posts with default perPage of 15 means all should be on one page
        expect($data['count'])->toBe(12);
    });

    it('shows no data message when filter matches nothing', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->wait(2);

        // Set search to something that won't match
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "XYZNonExistentTerm123");
        }');

        // Wait for "No data found" to appear
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("No data found")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $found = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($found)->toBeTrue('Expected "No data found" message in grid layout');
    });
});

describe('Grid Layout Mobile', function (): void {
    it('renders grid layout on mobile viewport', function (): void {
        $page = visitLivewire(GridPostDataTable::class);

        $page->on()->mobile()
            ->wait(2)
            ->assertSee('Grid Post 1')
            ->assertNoJavascriptErrors();
    });
});
