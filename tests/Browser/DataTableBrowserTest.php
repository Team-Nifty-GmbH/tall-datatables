<?php

/**
 * Browser Tests for DataTable Component
 *
 * These tests use Pest's browser testing capabilities with Playwright.
 * They test the DataTable component in a real browser environment using
 * the visitLivewire() helper to dynamically create routes for components.
 *
 * Requirements:
 * - pestphp/pest-plugin-browser must be installed
 * - npm install playwright@latest && npx playwright install
 * - npm run build (assets must be built)
 *
 * These tests are automatically skipped if assets are not built.
 * In CI, assets are built before running tests.
 */

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }
    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);

    for ($i = 1; $i <= 25; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post Title {$i}",
            'content' => "Content for post {$i} with some additional text for searching",
            'is_published' => $i % 2 === 0,
        ]);
    }
});

describe('DataTable Browser Rendering', function (): void {
    it('renders the datatable component on the page', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->assertSee('Title')
            ->assertNoJavascriptErrors();
    });

    it('displays table headers correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->assertSee('Title')
            ->assertSee('Content')
            ->assertSee('Is Published');
    });

    it('has no JavaScript errors on initial load', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->assertNoJavascriptErrors();
    });

    it('renders correctly on mobile viewport', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->on()->mobile()
            ->assertSee('Title')
            ->assertNoJavascriptErrors();
    });

    it('renders correctly in dark mode', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->inDarkMode()
            ->assertSee('Title')
            ->assertNoJavascriptErrors();
    });

    it('initializes with x-data attribute', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->assertPresent('[x-data]');
    });
});

describe('DataTable Browser Data Loading', function (): void {
    it('displays data after component initializes', function (): void {
        $page = visitLivewire(PostDataTable::class);

        // Wait for the data to load - Livewire renders are async
        $page->wait(2)
            ->assertSee('Post Title 1');
    });

    it('displays multiple rows of data', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2)
            ->assertSee('Post Title 1')
            ->assertSee('Post Title 2');
    });

    it('renders each column in separate td cells', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Check that the table has multiple td cells per row (not all in one)
        $result = $page->script('() => {
            const rows = document.querySelectorAll("tbody tr");
            let maxCellCount = 0;
            let dataRowCount = 0;
            let debugInfo = [];
            for (const row of rows) {
                const cells = row.querySelectorAll("td");
                debugInfo.push({ rowCells: cells.length, hasDataId: row.hasAttribute("data-id") });
                if (cells.length > 1) {
                    dataRowCount++;
                    if (cells.length > maxCellCount) {
                        maxCellCount = cells.length;
                    }
                }
            }
            return { rowCount: dataRowCount, cellCount: maxCellCount, debug: debugInfo, totalRows: rows.length };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['rowCount'])->toBeGreaterThan(0);
        expect($data['cellCount'])->toBeGreaterThanOrEqual(3);
    });
});

describe('DataTable Browser Performance', function (): void {
    it('loads without console errors', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Grouping', function (): void {
    it('renders grouped view with correct column structure', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable grouping by is_published via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setGroupBy", "is_published");
        }');

        // Wait for group header rows to appear in the DOM
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const groupHeaders = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
                    if (groupHeaders.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Expand the first group by clicking the group header
        $page->script('() => {
            const groupHeader = document.querySelector("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
            if (groupHeader) groupHeader.click();
        }');

        // Wait for expanded group data rows to appear
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const dataRows = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-row-\\"]");
                    if (dataRows.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Get HTML structure of grouped table
        $result = $page->script('() => {
            const tbody = document.querySelector("table tbody");
            let rowsInfo = [];
            if (tbody) {
                const rows = tbody.querySelectorAll("tr");
                for (const row of rows) {
                    const cells = row.querySelectorAll("td");
                    const computedDisplay = window.getComputedStyle(row).display;
                    const wireKey = row.getAttribute("wire:key") || "";
                    const isGroupRow = wireKey.startsWith("group-row-");
                    rowsInfo.push({
                        cellCount: cells.length,
                        isVisible: computedDisplay !== "none",
                        isGroupRow: isGroupRow,
                    });
                }
            }
            return { rows: rowsInfo };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        // Should have at least one visible data row with multiple cells
        $foundDataRow = false;
        foreach ($data['rows'] as $row) {
            if ($row['isVisible'] && $row['isGroupRow'] && $row['cellCount'] > 3) {
                $foundDataRow = true;
                expect($row['cellCount'])->toBeGreaterThanOrEqual(3);
            }
        }

        expect($foundDataRow)->toBeTrue('Expected to find at least one group data row with multiple cells');
    });

    it('shows groups pagination data when grouped', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable grouping by is_published via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setGroupBy", "is_published");
        }');

        // Wait for group headers to appear in the DOM
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        return resolve({ timeout: true, groupCount: 0 });
                    }
                    const groupHeaders = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
                    if (groupHeaders.length > 0) {
                        return resolve({ timeout: false, groupCount: groupHeaders.length });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grouped view');
        // With is_published being true/false, we expect 2 groups
        expect($data['groupCount'])->toBe(2);
    });

    it('shows aggregates in group header when aggregation is enabled', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Set aggregation for price column and then enable grouping
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: ["price"] });
                lw.call("applyAggregations");
            }
        }');

        $page->wait(2);

        // Enable grouping by is_published
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setGroupBy", "is_published");
        }');

        // Wait for group headers with aggregate text to appear
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const headers = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
                        return resolve({
                            timeout: true,
                            groupCount: headers.length,
                            firstHeaderText: headers[0]?.textContent?.trim() || "",
                        });
                    }
                    const headers = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
                    if (headers.length > 0) {
                        const firstText = headers[0]?.textContent?.trim() || "";
                        const hasAggregate = firstText.toLowerCase().includes("sum");
                        if (hasAggregate) {
                            return resolve({
                                timeout: false,
                                groupCount: headers.length,
                                firstHeaderText: firstText,
                                hasAggregate: true,
                            });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for aggregates in group headers');
        expect($data['hasAggregate'])->toBeTrue('Expected group header to contain aggregate (Sum) text');
    });
});

describe('DataTable Browser Sorting', function (): void {
    it('sorts by column when clicking header via sortTable', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2)
            ->assertSee('Post Title 1');

        // Sort by title ascending via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for userOrderBy to be set
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

        expect($data['timeout'])->toBeFalse('Timed out waiting for sort to apply');
        expect($data['orderBy'])->toBe('title');
    });

    it('toggles sort direction when clicking same column again', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Sort by title ascending
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for sort to apply
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("userOrderBy") === "title") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Sort by title again to toggle direction
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for direction to change
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const asc = lw?.$get("userOrderAsc");
                    if (asc === false) return resolve({ timeout: false, asc });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for sort direction to toggle');
        expect($data['asc'])->toBeFalse();
    });

    it('displays sort badge when column is sorted', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Sort by title
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for sort badge to appear containing "Order by"
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("Order by") || text.includes("order by")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $badgeFound = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($badgeFound)->toBeTrue('Expected sort badge with "Order by" to appear');
    });

    it('clears sort when sortTable is called with empty string', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply sort
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for sort
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("userOrderBy") === "title") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear sort
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "");
        }');

        // Wait for sort to be cleared
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const orderBy = window.Livewire?.find(wireId)?.$get("userOrderBy");
                    if (orderBy === "") return resolve({ timeout: false, orderBy });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for sort to clear');
        expect($data['orderBy'])->toBe('');
    });

    it('has no javascript errors during sort operations', function (): void {
        $page = visitLivewire(PostDataTable::class);

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

describe('DataTable Browser Pagination', function (): void {
    it('displays pagination info with correct totals', function (): void {
        $page = visitLivewire(PostDataTable::class);

        // Wait for data to load and pagination to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const text = document.body.textContent;
                    if (text.includes("Showing") && text.includes("results")) {
                        return resolve({ timeout: false, found: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for pagination info');
        $page->assertSee('Showing');
        $page->assertSee('results');
    });

    it('navigates to next page via gotoPage', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Get initial page
        $result = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("page") ?? 0;
        }');
        $initialPage = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($initialPage)->toBe(1);

        // Navigate to page 2
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("gotoPage", 2);
        }');

        // Wait for page to change
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, page: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const page = window.Livewire?.find(wireId)?.$get("page");
                    if (page === 2) return resolve({ timeout: false, page });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for page navigation');
        expect($data['page'])->toBe(2);
    });

    it('changes per page count via setPerPage', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Change perPage to 25
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setPerPage", 25);
        }');

        // Wait for perPage to update
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, perPage: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const perPage = window.Livewire?.find(wireId)?.$get("perPage");
                    if (perPage === 25) return resolve({ timeout: false, perPage });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for perPage change');
        expect($data['perPage'])->toBe(25);
    });

    it('shows all 25 rows when perPage is set to 25', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Change perPage to 25 so all posts appear on one page
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setPerPage", 25);
        }');

        // Wait for all rows to render
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const rows = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");
                        return resolve({ timeout: true, rowCount: rows.length });
                    }
                    const rows = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");
                    if (rows.length >= 25) return resolve({ timeout: false, rowCount: rows.length });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for all rows to load');
        expect($data['rowCount'])->toBe(25);
    });

    it('shows filtered data when filter narrows results from page 2', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Go to page 2 first
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("gotoPage", 2);
        }');

        // Wait for page 2 data to load
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("page") === 2) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Apply a narrow filter that matches only 1 post. The server-side
        // loadData() will detect an empty page 2 and auto-reset to page 1.
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title 25", 0, 0);
        }');

        // Verify the filtered result appears in the DOM (proves data loaded correctly)
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 15000) {
                        return resolve({ timeout: true, hasResult: false });
                    }
                    if (document.body.textContent.includes("Post Title 25")) {
                        return resolve({ timeout: false, hasResult: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filtered data');
        expect($data['hasResult'])->toBeTrue('Expected "Post Title 25" to appear after filter');
    });

    it('has no javascript errors during pagination', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("gotoPage", 2);
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Search', function (): void {
    it('sets search property and filters results', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2)
            ->assertSee('Post Title 1');

        // Set search via $wire.$set which triggers updatedSearch -> startSearch
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "Post Title 1");
        }');

        // Wait for search to take effect
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const search = window.Livewire?.find(wireId)?.$get("search");
                    if (search === "Post Title 1") return resolve({ timeout: false, search });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for search to apply');
        expect($data['search'])->toBe('Post Title 1');
    });

    it('displays search badge when search is active', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Set search term
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "UniqueSearchTerm");
        }');

        // Wait for search badge to appear
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("Search") && text.includes("UniqueSearchTerm")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $badgeFound = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($badgeFound)->toBeTrue('Expected search badge with "UniqueSearchTerm" to appear');
    });

    it('clears search when clearFiltersAndSort is called', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Set search
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "test");
        }');

        // Wait for search to take effect
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("search") === "test") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear everything
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("clearFiltersAndSort");
        }');

        // Wait for search to be cleared
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const search = window.Livewire?.find(wireId)?.$get("search");
                    if (search === "") return resolve({ timeout: false, cleared: true });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for search to clear');
        expect($data['cleared'])->toBeTrue();
    });

    it('resets page to 1 when search changes', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Go to page 2 first
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("gotoPage", 2);
        }');

        // Wait for page 2
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("page") === 2) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Set search which triggers startSearch (resets page)
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "Post");
        }');

        // Wait for page to reset
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, page: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const page = window.Livewire?.find(wireId)?.$get("page");
                    const search = window.Livewire?.find(wireId)?.$get("search");
                    if (search === "Post" && page === 1) return resolve({ timeout: false, page });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for page reset on search');
        expect($data['page'])->toBe(1);
    });

    it('has no javascript errors during search', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "something");
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Column Visibility', function (): void {
    it('can change enabled columns via storeColLayout', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Get initial column count
        $result = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("enabledCols")?.length ?? 0;
        }');
        $initialCount = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($initialCount)->toBe(5);

        // Remove a column by setting enabledCols via storeColLayout
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("storeColLayout", ["title", "content", "price", "is_published"]);
        }');

        // Wait for enabledCols to update
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, count: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const cols = window.Livewire?.find(wireId)?.$get("enabledCols") ?? [];
                    if (cols.length === 4) return resolve({ timeout: false, count: cols.length, cols });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for column update');
        expect($data['count'])->toBe(4);
        expect($data['cols'])->not->toContain('created_at');
    });

    it('has no javascript errors when changing columns', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("storeColLayout", ["title", "content"]);
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Filtering', function (): void {
    it('filters data when typing in a text filter input', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2)
            ->assertSee('Post Title 1');

        // Apply filter by calling setTextFilter directly on the Livewire component
        // This is the same function that x-on:input.debounce.500ms calls
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title 1", 0, 0);
        }');

        // Wait for filter to take effect by polling userFilters
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, filterCount: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve({ timeout: false, filterCount: uf.length });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filter to apply');
        expect($data['filterCount'])->toBeGreaterThan(0);

        // "Post Title 1" should still be visible
        $page->assertSee('Post Title 1');
    });

    it('uses wire.userFilters as single source of truth', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Verify that $wire.userFilters is accessible and is an array
        $result = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const uf = window.Livewire?.find(wireId)?.$get("userFilters");
            return { isArray: Array.isArray(uf), type: typeof uf };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['isArray'])->toBeTrue();
    });

    it('clears filters when clear button is clicked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title 1", 0, 0);
        }');

        // Wait for filter to take effect (userFilters length > 0)
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear all filters via Livewire
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("clearFiltersAndSort");
        }');

        // Wait for userFilters to be empty again
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length === 0) return resolve({ timeout: false, filtersCleared: true });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for filters to clear');
        expect($data['filtersCleared'])->toBeTrue();
    });

    it('updates visible row data after removing a filter via removeFilter', function (): void {
        $user = createTestUser(['name' => 'Filter Test User', 'email' => 'filter-row@example.com']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueAlpha Post']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueAlpha Second']);

        $page = visitLivewire(PostDataTable::class);

        // Wait for initial data to load
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    if (document.body.textContent.includes("Post Title 1")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->assertSee('Post Title 1');

        // Apply filter for "UniqueAlpha" via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "UniqueAlpha", 0, 0);
        }');

        // Wait until filter takes effect - poll for "UniqueAlpha Post" to appear AND "Post Title 1" to disappear
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("UniqueAlpha") && !text.includes("Post Title 1")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->assertSee('UniqueAlpha Post')
            ->assertDontSee('Post Title 1');

        // Remove filter via Livewire removeFilter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("removeFilter", 0, 0);
        }');

        // Poll until "Post Title 1" reappears
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        return resolve({ timeout: true, hasPostTitle: document.body.textContent.includes("Post Title 1") });
                    }
                    if (document.body.textContent.includes("Post Title 1")) {
                        return resolve({ timeout: false, hasPostTitle: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for data to reload after removing filter');
        expect($data['hasPostTitle'])->toBeTrue();
    });

    it('updates visible row data after clearFilters', function (): void {
        $user = createTestUser(['name' => 'Clear Test User', 'email' => 'clear-row@example.com']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueBeta Post']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueBeta Second']);

        $page = visitLivewire(PostDataTable::class);

        // Wait for initial data to load
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    if (document.body.textContent.includes("Post Title 1")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Apply filter for "UniqueBeta" via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "UniqueBeta", 0, 0);
        }');

        // Wait until filter takes effect
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const text = document.body.textContent;
                    if (text.includes("UniqueBeta") && !text.includes("Post Title 1")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->assertSee('UniqueBeta Post')
            ->assertDontSee('Post Title 1');

        // Clear all filters via Livewire
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("clearFiltersAndSort");
        }');

        // Poll until "Post Title 1" reappears
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        return resolve({ timeout: true, hasPostTitle: document.body.textContent.includes("Post Title 1") });
                    }
                    if (document.body.textContent.includes("Post Title 1")) {
                        return resolve({ timeout: false, hasPostTitle: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for data to reload after clearing filters');
        expect($data['hasPostTitle'])->toBeTrue();
    });

    it('displays filter badge when filter is applied', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter via Livewire call
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "xyz", 0, 0);
        }');

        // Wait for filter badge to appear (server-rendered head badges strip % wildcards)
        // The badge shows "xyz" not "%xyz%" because head.blade.php strips wildcards
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0 && document.body.textContent.includes("xyz")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');
        $badgeFound = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($badgeFound)->toBeTrue('Expected filter badge containing "xyz" to appear');
    });
});

describe('DataTable Browser Aggregation Footer', function (): void {
    it('shows tfoot aggregate row after enabling sum aggregation', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable sum aggregation on the price column and apply
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: ["price"], avg: [], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        // Wait for the tfoot to contain an aggregate row with "Sum" text
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const tfoot = document.querySelector("tfoot");
                        return resolve({ timeout: true, tfootText: tfoot?.textContent?.trim()?.substring(0, 200) || "" });
                    }
                    const tfoot = document.querySelector("tfoot");
                    if (tfoot && tfoot.textContent.toLowerCase().includes("sum")) {
                        return resolve({ timeout: false, hasSumRow: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for sum aggregate row in tfoot');
        expect($data['hasSumRow'])->toBeTrue();
    });

    it('shows multiple aggregate types simultaneously', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable both sum and avg aggregation on price
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: ["price"], avg: ["price"], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        // Wait for both sum and avg rows to appear in tfoot
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const tfoot = document.querySelector("tfoot");
                        return resolve({ timeout: true, tfootText: tfoot?.textContent?.trim()?.substring(0, 300) || "" });
                    }
                    const tfoot = document.querySelector("tfoot");
                    if (tfoot) {
                        const text = tfoot.textContent.toLowerCase();
                        const hasSum = text.includes("sum");
                        const hasAvg = text.includes("avg");
                        if (hasSum && hasAvg) {
                            return resolve({ timeout: false, hasSum: true, hasAvg: true });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for multiple aggregate rows');
        expect($data['hasSum'])->toBeTrue();
        expect($data['hasAvg'])->toBeTrue();
    });

    it('clears aggregation when aggregatableCols is reset', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable aggregation first
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: ["price"], avg: [], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        // Wait for sum row to appear
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const tfoot = document.querySelector("tfoot");
                    if (tfoot && tfoot.textContent.toLowerCase().includes("sum")) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        // Clear aggregation
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: [], avg: [], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        // Wait for the sum row to disappear from tfoot
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const tfoot = document.querySelector("tfoot");
                        return resolve({ timeout: true, stillHasSum: tfoot?.textContent?.toLowerCase()?.includes("sum") ?? false });
                    }
                    const tfoot = document.querySelector("tfoot");
                    if (tfoot && !tfoot.textContent.toLowerCase().includes("sum")) {
                        return resolve({ timeout: false, cleared: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for aggregation to clear');
        expect($data['cleared'])->toBeTrue();
    });

    it('displays min and max aggregate values correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable min and max aggregation on price
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: [], avg: [], min: ["price"], max: ["price"] });
                lw.call("applyAggregations");
            }
        }');

        // Wait for min and max rows in tfoot
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        const tfoot = document.querySelector("tfoot");
                        return resolve({ timeout: true, tfootText: tfoot?.textContent?.substring(0, 300) || "" });
                    }
                    const tfoot = document.querySelector("tfoot");
                    if (tfoot) {
                        const text = tfoot.textContent.toLowerCase();
                        if (text.includes("min") && text.includes("max")) {
                            return resolve({ timeout: false, hasMin: true, hasMax: true });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 1000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for min/max aggregate rows');
        expect($data['hasMin'])->toBeTrue();
        expect($data['hasMax'])->toBeTrue();
    });

    it('has no javascript errors during aggregation operations', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.$set("aggregatableCols", { sum: ["price"], avg: ["price"], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Combined Operations', function (): void {
    it('applies filter and sort together correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a text filter for "Post Title" (matches all posts)
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title", 0, 0);
        }');

        // Wait for filter to take effect
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Now also sort by title
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for both filter and sort to be active
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const orderBy = lw?.$get("userOrderBy");
                    const filters = lw?.$get("userFilters") ?? [];
                    if (orderBy === "title" && filters.length > 0) {
                        return resolve({ timeout: false, orderBy, filterCount: filters.length });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filter + sort combination');
        expect($data['orderBy'])->toBe('title');
        expect($data['filterCount'])->toBeGreaterThan(0);
    });

    it('search narrows results and pagination adjusts', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Verify we start with pagination (25 posts, default 15 per page)
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const text = document.body.textContent;
                    if (text.includes("Showing") && text.includes("25")) {
                        return resolve({ timeout: false, hasPagination: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for initial pagination');

        // Search for a specific post that will narrow to fewer results
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "Post Title 25");
        }');

        // Wait for search to narrow results - should see "Post Title 25" and fewer total results
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const text = document.body.textContent;
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const search = window.Livewire?.find(wireId)?.$get("search");
                    if (search === "Post Title 25" && text.includes("Post Title 25")) {
                        return resolve({ timeout: false, hasResult: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for search to narrow results');
        expect($data['hasResult'])->toBeTrue();
    });

    it('filter sort and search combined work simultaneously', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title", 0, 0);
        }');

        // Wait for filter
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const uf = window.Livewire?.find(wireId)?.$get("userFilters") ?? [];
                    if (uf.length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Apply sort
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        // Wait for sort
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("userOrderBy") === "title") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Apply search
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.$set("search", "Post");
        }');

        // Verify all three are active
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const orderBy = lw?.$get("userOrderBy");
                    const filters = lw?.$get("userFilters") ?? [];
                    const search = lw?.$get("search");
                    if (orderBy === "title" && filters.length > 0 && search === "Post") {
                        return resolve({
                            timeout: false,
                            orderBy,
                            filterCount: filters.length,
                            search,
                        });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filter + sort + search combination');
        expect($data['orderBy'])->toBe('title');
        expect($data['filterCount'])->toBeGreaterThan(0);
        expect($data['search'])->toBe('Post');
    });

    it('clearFiltersAndSort resets everything at once', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter, sort, and search
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            lw?.call("setTextFilter", "title", "Post", 0, 0);
        }');

        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("userFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            lw?.call("sortTable", "title");
            lw?.$set("search", "something");
        }');

        // Wait for all to be applied
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    if (lw?.$get("userOrderBy") === "title" && lw?.$get("search") === "something") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear everything
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("clearFiltersAndSort");
        }');

        // Verify all are reset
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const orderBy = lw?.$get("userOrderBy");
                    const filters = lw?.$get("userFilters") ?? [];
                    const search = lw?.$get("search");
                    if (orderBy === "" && filters.length === 0 && search === "") {
                        return resolve({
                            timeout: false,
                            orderBy,
                            filterCount: filters.length,
                            search,
                        });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for clearFiltersAndSort to reset everything');
        expect($data['orderBy'])->toBe('');
        expect($data['filterCount'])->toBe(0);
        expect($data['search'])->toBe('');
    });

    it('grouping and filtering work together', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a text filter first
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "Post Title", 0, 0);
        }');

        // Wait for filter
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("userFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Enable grouping
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setGroupBy", "is_published");
        }');

        // Wait for group headers to appear while filter is still active
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, groupCount: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const filters = lw?.$get("userFilters") ?? [];
                    const groupHeaders = document.querySelectorAll("tbody tr[wire\\\\:key^=\\"group-header-\\"]");
                    if (groupHeaders.length > 0 && filters.length > 0) {
                        return resolve({
                            timeout: false,
                            groupCount: groupHeaders.length,
                            filterCount: filters.length,
                        });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for grouping + filtering combination');
        expect($data['groupCount'])->toBeGreaterThan(0);
        expect($data['filterCount'])->toBeGreaterThan(0);
    });

    it('has no javascript errors during combined operations', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter, sort, search, and aggregation all at once
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            if (lw) {
                lw.call("setTextFilter", "title", "Post", 0, 0);
                lw.call("sortTable", "title");
                lw.$set("search", "Post");
                lw.$set("aggregatableCols", { sum: ["price"], avg: [], min: [], max: [] });
                lw.call("applyAggregations");
            }
        }');

        $page->wait(3)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Sidebar Panel', function (): void {
    it('opens the sidebar when the settings button is clicked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Click the settings (cog) button to open the sidebar slide
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const slideId = "data-table-sidebar-" + wireId.toLowerCase();
            // Use TallStackUi API to open the slide
            window.__Tallstackui?.slide?.open?.(slideId) ?? window.dispatchEvent(new CustomEvent("open-slide", { detail: { id: slideId } }));
        }');

        // Also try clicking the actual button in case the API approach differs
        $page->script('() => {
            // Find and click the cog-6-tooth button (sidebar trigger)
            const buttons = document.querySelectorAll("button");
            for (const btn of buttons) {
                const svg = btn.querySelector("svg");
                if (svg && btn.closest("th")) {
                    const onclick = btn.getAttribute("x-on:click") || "";
                    if (onclick.includes("open.slide")) {
                        btn.click();
                        break;
                    }
                }
            }
        }');

        // Wait for the slide panel to become visible
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) {
                        return resolve({ timeout: true });
                    }
                    // Check for the aside element or the slide container being visible
                    const slides = document.querySelectorAll("[x-data*=\\"slide\\"], [data-slide]");
                    for (const slide of slides) {
                        const style = window.getComputedStyle(slide);
                        if (style.display !== "none" && style.visibility !== "hidden") {
                            return resolve({ timeout: false, sidebarOpen: true });
                        }
                    }
                    // Also check for any visible options content like tabs (Filters, Columns, etc.)
                    const text = document.body.textContent;
                    if (text.includes("Filters") && text.includes("Columns")) {
                        return resolve({ timeout: false, sidebarOpen: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for sidebar to open');
        expect($data['sidebarOpen'])->toBeTrue();
    });

    it('has no javascript errors when opening sidebar', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Click the cog button
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

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});

describe('DataTable Browser Saved Filters', function (): void {
    it('saves current filter state and appears in savedFilters', function (): void {
        // Must be logged in for saved filters to work
        $this->actingAs($this->user);

        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a filter first
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setTextFilter", "title", "SavedFilterTest", 0, 0);
        }');

        // Wait for filter to take effect
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("userFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Save the filter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("saveFilter", "My Test Filter", false, true);
        }');

        // Wait for savedFilters to contain the new filter
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, filterCount: 0 });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const saved = window.Livewire?.find(wireId)?.$get("savedFilters") ?? [];
                    if (saved.length > 0) {
                        const names = saved.map(f => f.name);
                        return resolve({ timeout: false, filterCount: saved.length, names });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filter to be saved');
        expect($data['filterCount'])->toBeGreaterThan(0);
        expect($data['names'])->toContain('My Test Filter');
    });

    it('loads a saved filter and applies its settings', function (): void {
        $this->actingAs($this->user);

        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a specific filter and sort, then save
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            lw?.call("setTextFilter", "title", "LoadTest", 0, 0);
        }');

        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("userFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Sort by title
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("sortTable", "title");
        }');

        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if (window.Livewire?.find(wireId)?.$get("userOrderBy") === "title") return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Save the filter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("saveFilter", "LoadableFilter", false, true);
        }');

        // Wait for save
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("savedFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Clear all current filters and sort
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("clearFiltersAndSort");
        }');

        // Wait for clear
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    if (lw?.$get("userOrderBy") === "" && (lw?.$get("userFilters") ?? []).length === 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Load the saved filter by getting its settings and calling loadFilter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            const saved = lw?.$get("savedFilters") ?? [];
            const filter = saved.find(f => f.name === "LoadableFilter");
            if (filter) {
                lw.call("loadFilter", filter.settings);
            }
        }');

        // Verify the loaded filter restored orderBy and userFilters
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const lw = window.Livewire?.find(wireId);
                    const orderBy = lw?.$get("userOrderBy");
                    const filters = lw?.$get("userFilters") ?? [];
                    if (orderBy === "title" && filters.length > 0) {
                        return resolve({ timeout: false, orderBy, filterCount: filters.length });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for saved filter to load');
        expect($data['orderBy'])->toBe('title');
        expect($data['filterCount'])->toBeGreaterThan(0);
    });

    it('deletes a saved filter', function (): void {
        $this->actingAs($this->user);

        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Create a filter to then delete
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            lw?.call("setTextFilter", "title", "DeleteMe", 0, 0);
        }');

        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve(false);
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    if ((window.Livewire?.find(wireId)?.$get("userFilters") ?? []).length > 0) return resolve(true);
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        // Save the filter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("saveFilter", "FilterToDelete", false, true);
        }');

        // Wait for save and get the filter ID
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const saved = window.Livewire?.find(wireId)?.$get("savedFilters") ?? [];
                    const filter = saved.find(f => f.name === "FilterToDelete");
                    if (filter) return resolve({ timeout: false, id: filter.id, count: saved.length });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for filter to be saved');

        $filterId = $data['id'];
        $savedCount = $data['count'];

        // Delete the filter
        $page->script("() => {
            const comp = document.querySelector('[wire\\\\:id]');
            const wireId = comp?.getAttribute('wire:id');
            window.Livewire?.find(wireId)?.call('deleteSavedFilter', '{$filterId}');
        }");

        // Verify the filter was removed from savedFilters
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const saved = window.Livewire?.find(wireId)?.$get("savedFilters") ?? [];
                    const filter = saved.find(f => f.name === "FilterToDelete");
                    if (!filter) return resolve({ timeout: false, deleted: true, remaining: saved.length });
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['timeout'])->toBeFalse('Timed out waiting for filter to be deleted');
        expect($data['deleted'])->toBeTrue();
        expect($data['remaining'])->toBeLessThan($savedCount);
    });

    it('has no javascript errors during saved filter operations', function (): void {
        $this->actingAs($this->user);

        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Save a filter
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("saveFilter", "NoErrorFilter", false, true);
        }');

        $page->wait(2);

        // Load it
        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            const lw = window.Livewire?.find(wireId);
            const saved = lw?.$get("savedFilters") ?? [];
            if (saved.length > 0) {
                lw.call("loadFilter", saved[0].settings);
            }
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});
