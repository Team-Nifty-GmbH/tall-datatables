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
        // Each data row should have multiple td elements
        $result = $page->script('() => {
            const rows = document.querySelectorAll("tbody tr");
            // Find data rows by looking for rows that have data-id attribute set by Alpine
            let maxCellCount = 0;
            let dataRowCount = 0;
            let debugInfo = [];
            for (const row of rows) {
                const cells = row.querySelectorAll("td");
                debugInfo.push({ rowCells: cells.length, hasDataId: row.hasAttribute("data-id") });
                // Skip rows with no cells or only 1 cell (likely header/placeholder)
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
        // Should have at least 3 cells per row to confirm columns are separate
        // (not all jammed into one td)
        expect($data['cellCount'])->toBeGreaterThanOrEqual(3);
    });
});

describe('DataTable Browser Formatters', function (): void {
    it('formats money values correctly via evaluate', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.money(1234.56)');

        expect($result)->toContain('1');
    });

    it('formats boolean true values with emerald background', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.bool(true)');

        expect($result)->toContain('bg-emerald');
    });

    it('formats boolean false values with red background', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.bool(false)');

        expect($result)->toContain('bg-red');
    });

    it('formats date values correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.date('2024-01-15')");

        expect($result)->toContain('2024');
    });

    it('formats datetime values correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.datetime('2024-01-15T10:30:00')");

        expect($result)->toContain('2024');
    });

    it('formats percentage values correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.percentage(0.5)');

        expect($result)->toContain('50');
    });

    it('formats email values as mailto links', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.email('test@example.com')");

        expect($result)->toContain('mailto:');
    });

    it('formats url values as links', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.url('https://example.com')");

        expect($result)->toContain('href');
    });

    it('formats tel values as tel links', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.tel('+1234567890')");

        expect($result)->toContain('tel:');
    });

    it('formats image values with img tag', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.image('https://example.com/image.jpg')");

        expect($result)->toContain('<img');
    });

    it('formats array values as badges', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.array(['one', 'two', 'three'])");

        expect($result)->toContain('inline-flex');
    });

    it('returns correct input type for date formatter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.inputType('date')");

        expect($result)->toBe('date');
    });

    it('returns correct input type for datetime formatter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.inputType('datetime')");

        expect($result)->toBe('datetime-local');
    });

    it('returns correct input type for money formatter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.inputType('money')");

        expect($result)->toBe('number');
    });

    it('returns correct input type for email formatter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.inputType('email')");

        expect($result)->toBe('email');
    });

    it('guessType correctly identifies null', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.guessType(null)');

        expect($result)->toBe('null');
    });

    it('guessType correctly identifies arrays', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.guessType([1, 2, 3])');

        expect($result)->toBe('array');
    });

    it('guessType correctly identifies objects', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.guessType({a: 1})');

        expect($result)->toBe('object');
    });

    it('guessType correctly identifies booleans', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.guessType(true)');

        expect($result)->toBe('boolean');
    });

    it('guessType correctly identifies emails', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.guessType('test@test.com')");

        expect($result)->toBe('email');
    });

    it('guessType correctly identifies dates', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.guessType('2024-01-15')");

        expect($result)->toBe('date');
    });

    it('guessType correctly identifies datetimes', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.guessType('2024-01-15T10:30:00')");

        expect($result)->toBe('datetime');
    });

    it('formats time from milliseconds correctly', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.time(3661000)');

        expect($result)->toBe('01:01:01');
    });

    it('formats negative time values with minus sign', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.time(-3600000)');

        expect($result)->toContain('-');
    });

    it('formats colored money with red for negative values', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.coloredMoney(-100)');

        expect($result)->toContain('text-red');
    });

    it('formats colored money with emerald for positive values', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script('() => window.formatters.coloredMoney(100)');

        expect($result)->toContain('text-emerald');
    });

    it('formats badge with custom colors', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $result = $page->script("() => window.formatters.badge('active', 'green')");

        expect($result)->toContain('bg-green');
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

        // Enable grouping by is_published
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].$wire.setGroupBy("is_published");
            }
        }');

        $page->wait(2);

        // Expand the first group by calling toggleGroup directly via Livewire
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                // Get the first group key
                const groups = xDataEl._x_dataStack[0].getGroups();
                if (groups && groups.length > 0) {
                    xDataEl._x_dataStack[0].$wire.toggleGroup(groups[0].key);
                }
            }
        }');

        $page->wait(2);

        // Get HTML structure of grouped table
        $result = $page->script('() => {
            const table = document.querySelector("table");
            const tbody = table ? table.querySelector("tbody") : null;

            let rowsInfo = [];
            if (tbody) {
                const rows = tbody.querySelectorAll("tr");

                for (const row of rows) {
                    const cells = row.querySelectorAll("td");
                    const computedDisplay = window.getComputedStyle(row).display;
                    const rowType = row.getAttribute("data-row-type");

                    rowsInfo.push({
                        cellCount: cells.length,
                        isVisible: computedDisplay !== "none",
                        rowType: rowType,
                    });
                }
            }

            return { rows: rowsInfo };
        }');

        // The actual assertion: grouped rows should have multiple cells
        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        // Should have at least one visible data row with multiple cells
        $foundDataRow = false;
        foreach ($data['rows'] as $row) {
            // Look for visible data rows with more than 3 cells
            if ($row['isVisible'] && $row['rowType'] === 'data' && $row['cellCount'] > 3) {
                $foundDataRow = true;
                // Should have at least 5 cells (the columns plus checkbox/sidebar)
                expect($row['cellCount'])->toBeGreaterThanOrEqual(5);
            }
        }

        expect($foundDataRow)->toBeTrue('Expected to find at least one data row with multiple cells');
    });

    it('shows groups pagination when there are many groups', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Enable grouping by is_published
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].$wire.setGroupBy("is_published");
            }
        }');

        $page->wait(2);

        // Check if getGroupsPagination() returns data
        $result = $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                const pagination = xDataEl._x_dataStack[0].getGroupsPagination();
                const data = xDataEl._x_dataStack[0].data;
                return {
                    pagination: pagination,
                    dataKeys: Object.keys(data),
                    hasGroupsPagination: data.hasOwnProperty("groups_pagination"),
                    rawGroupsPagination: data.groups_pagination
                };
            }
            return null;
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        // Groups pagination should exist in the data
        expect($data['hasGroupsPagination'])->toBeTrue();
        expect($data['pagination'])->not->toBeNull();
        expect($data['pagination'])->toHaveKey('current_page');
        expect($data['pagination'])->toHaveKey('last_page');
        expect($data['pagination'])->toHaveKey('total');

        // With only 2 groups (true/false), we should have 1 page
        // The pagination UI only shows when last_page > 1
        expect($data['pagination']['total'])->toBe(2);
        expect($data['pagination']['last_page'])->toBe(1);
    });

    it('shows aggregates in group header when aggregation is enabled', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Set aggregation for price column
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].aggregatableCols = { sum: ["price"] };
                xDataEl._x_dataStack[0].$wire.applyAggregations();
            }
        }');

        $page->wait(2);

        // Enable grouping by is_published
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].$wire.setGroupBy("is_published");
            }
        }');

        $page->wait(2);

        // Check that groups have aggregates in their data (shown in header)
        $result = $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                const groups = xDataEl._x_dataStack[0].getGroups();
                return {
                    groupsCount: groups.length,
                    firstGroupHasAggregates: groups.length > 0 && groups[0].aggregates && Object.keys(groups[0].aggregates).length > 0,
                    firstGroupAggregateTypes: groups.length > 0 && groups[0].aggregates ? Object.keys(groups[0].aggregates) : []
                };
            }
            return null;
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        // Groups should have aggregates even without expanding (shown in header)
        expect($data['firstGroupHasAggregates'])->toBeTrue();
        expect($data['firstGroupAggregateTypes'])->toContain('sum');
    });
});

describe('DataTable Browser Filtering', function (): void {
    it('filters data when typing in a text filter input', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2)
            ->assertSee('Post Title 1');

        // Get initial row count
        $before = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $beforeTotal = is_array($before) && isset($before[0]) ? $before[0] : $before;
        expect($beforeTotal)->toBe(25);

        // Type in the title text filter (first searchbox in second header row)
        $page->script('() => {
            const input = document.querySelector("table thead tr:nth-child(2) td input[type=search]");
            if (input) {
                input.value = "Post Title 1";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }');

        // Wait for the debounce + Livewire request
        $page->wait(3);

        // Verify the data was filtered on the server
        $after = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $afterTotal = is_array($after) && isset($after[0]) ? $after[0] : $after;

        // "Post Title 1" matches: 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 = 11 posts
        expect($afterTotal)->toBeLessThan($beforeTotal)
            ->and($afterTotal)->toBeGreaterThan(0);
    });

    it('sends a livewire request when filter setter is called', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Directly test that the setter triggers a server call via syncFromAlpine
        $result = $page->script('() => {
            const tableEl = document.querySelector("[x-data]");
            const scope = tableEl?._x_dataStack?.[0];
            if (!scope) return { error: "no scope" };

            const desc = Object.getOwnPropertyDescriptor(scope, "filters");
            const setterStr = desc?.set?.toString() || "";

            return {
                hasSetter: !!desc?.set,
                usesSyncFromAlpine: setterStr.includes("syncFromAlpine"),
                setterSource: setterStr.substring(0, 200),
            };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['hasSetter'])->toBeTrue();
        // The setter MUST use $wire.call('syncFromAlpine', ...) to trigger server updates
        expect($data['usesSyncFromAlpine'])->toBeTrue('Filter setter must use syncFromAlpine to trigger server updates');
    });

    it('clears filters when clear button is clicked', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply a filter first
        $page->script('() => {
            const input = document.querySelector("table thead tr:nth-child(2) td input[type=search]");
            if (input) {
                input.value = "Post Title 1";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }');

        $page->wait(3);

        // Get filtered count
        $filtered = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $filteredTotal = is_array($filtered) && isset($filtered[0]) ? $filtered[0] : $filtered;
        expect($filteredTotal)->toBeLessThan(25);

        // Click clear button via JS (text is locale-dependent)
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].clearFilters();
            }
        }');
        $page->wait(3);

        // Verify all data is back
        $after = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $afterTotal = is_array($after) && isset($after[0]) ? $after[0] : $after;
        expect($afterTotal)->toBe(25);
    });

    it('updates visible row data after removing a filter via removeFilter', function (): void {
        $user = createTestUser(['name' => 'Filter Test User', 'email' => 'filter-row@example.com']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueAlpha Post']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueAlpha Second']);

        $page = visitLivewire(PostDataTable::class);

        // Wait for data to load
        $page->wait(3);

        // Poll until data is loaded
        $page->script('() => {
            return new Promise((resolve) => {
                const check = () => {
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const total = window.Livewire?.find(wireId)?.$get("data")?.total ?? 0;
                    if (total > 0) return resolve(total);
                    setTimeout(check, 200);
                };
                check();
            });
        }');

        // Verify unfiltered total
        $beforeTotal = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $beforeTotal = is_array($beforeTotal) && isset($beforeTotal[0]) ? $beforeTotal[0] : $beforeTotal;
        expect($beforeTotal)->toBe(27);

        // Apply filter for "UniqueAlpha"
        $page->script('() => {
            const input = document.querySelector("table thead tr:nth-child(2) td input[type=search]");
            if (input) {
                input.value = "UniqueAlpha";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }');

        // Wait for filter + poll until total changes
        $page->script('() => {
            return new Promise((resolve) => {
                const check = () => {
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const total = window.Livewire?.find(wireId)?.$get("data")?.total ?? 0;
                    if (total > 0 && total < 27) return resolve(total);
                    setTimeout(check, 200);
                };
                setTimeout(check, 1000);
            });
        }');

        $filteredTotal = $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            return window.Livewire?.find(wireId)?.$get("data")?.total;
        }');
        $filteredTotal = is_array($filteredTotal) && isset($filteredTotal[0]) ? $filteredTotal[0] : $filteredTotal;
        expect($filteredTotal)->toBe(2);

        // Remove filter via removeFilter (simulates clicking X on badge)
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].removeFilter(0, 0);
            }
        }');

        // Poll until BOTH total=27 AND data rows contain non-UniqueAlpha titles
        $debugResult = $page->script('() => {
            return new Promise((resolve, reject) => {
                const startTime = Date.now();
                const check = () => {
                    if (Date.now() - startTime > 10000) {
                        // Timeout - capture debug state
                        const comp = document.querySelector("[wire\\\\:id]");
                        const wireId = comp?.getAttribute("wire:id");
                        const lw = window.Livewire?.find(wireId);
                        const data = lw?.$get("data");
                        return resolve({
                            timeout: true,
                            total: data?.total,
                            rowCount: data?.data?.length,
                            titles: (data?.data || []).slice(0, 5).map(r => r.title),
                            allKeys: data ? Object.keys(data) : [],
                        });
                    }
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const data = window.Livewire?.find(wireId)?.$get("data");
                    const total = data?.total ?? 0;
                    const rows = data?.data || [];
                    const hasNonAlpha = rows.some(r => !r.title?.includes("UniqueAlpha"));
                    if (total === 27 && hasNonAlpha) {
                        return resolve({
                            timeout: false,
                            total: total,
                            rowCount: rows.length,
                            titles: rows.slice(0, 5).map(r => r.title),
                        });
                    }
                    setTimeout(check, 200);
                };
                setTimeout(check, 500);
            });
        }');
        $debugResult = is_array($debugResult) && isset($debugResult[0]) ? $debugResult[0] : $debugResult;

        expect($debugResult['timeout'])->toBeFalse(
            'Timed out waiting for data update. Debug: '
            . json_encode($debugResult)
        );

        $hasNonAlpha = collect($debugResult['titles'])->contains(fn ($t) => ! str_contains($t, 'UniqueAlpha'));
        expect($hasNonAlpha)->toBeTrue(
            'After removing filter, data must show mixed titles. '
            . 'Got: ' . json_encode($debugResult)
        );
    });

    it('updates visible row data after clearFilters', function (): void {
        $user = createTestUser(['name' => 'Clear Test User', 'email' => 'clear-row@example.com']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueBeta Post']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'UniqueBeta Second']);

        $page = visitLivewire(PostDataTable::class);

        // Wait for data to load
        $page->script('() => {
            return new Promise((resolve) => {
                const check = () => {
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const total = window.Livewire?.find(wireId)?.$get("data")?.total ?? 0;
                    if (total > 0) return resolve(total);
                    setTimeout(check, 200);
                };
                setTimeout(check, 1000);
            });
        }');

        // Apply filter for "UniqueBeta"
        $page->script('() => {
            const input = document.querySelector("table thead tr:nth-child(2) td input[type=search]");
            if (input) {
                input.value = "UniqueBeta";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }');

        // Wait until filter takes effect
        $page->script('() => {
            return new Promise((resolve) => {
                const check = () => {
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const total = window.Livewire?.find(wireId)?.$get("data")?.total ?? 0;
                    if (total > 0 && total < 27) return resolve(total);
                    setTimeout(check, 200);
                };
                setTimeout(check, 1000);
            });
        }');

        // Clear all filters
        $page->script('() => {
            const xDataEl = document.querySelector("[x-data]");
            if (xDataEl && xDataEl._x_dataStack && xDataEl._x_dataStack[0]) {
                xDataEl._x_dataStack[0].clearFilters();
            }
        }');

        // Poll until BOTH total=27 AND data rows contain non-UniqueBeta titles
        $debugResult = $page->script('() => {
            return new Promise((resolve) => {
                const startTime = Date.now();
                const check = () => {
                    if (Date.now() - startTime > 10000) {
                        const comp = document.querySelector("[wire\\\\:id]");
                        const wireId = comp?.getAttribute("wire:id");
                        const data = window.Livewire?.find(wireId)?.$get("data");
                        return resolve({
                            timeout: true,
                            total: data?.total,
                            rowCount: data?.data?.length,
                            titles: (data?.data || []).slice(0, 5).map(r => r.title),
                        });
                    }
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const data = window.Livewire?.find(wireId)?.$get("data");
                    const total = data?.total ?? 0;
                    const rows = data?.data || [];
                    const hasNonBeta = rows.some(r => !r.title?.includes("UniqueBeta"));
                    if (total === 27 && hasNonBeta) {
                        return resolve({
                            timeout: false,
                            total: total,
                            rowCount: rows.length,
                            titles: rows.slice(0, 5).map(r => r.title),
                        });
                    }
                    setTimeout(check, 200);
                };
                setTimeout(check, 500);
            });
        }');
        $debugResult = is_array($debugResult) && isset($debugResult[0]) ? $debugResult[0] : $debugResult;

        expect($debugResult['timeout'])->toBeFalse(
            'Timed out waiting for data update. Debug: '
            . json_encode($debugResult)
        );

        $hasNonBeta = collect($debugResult['titles'])->contains(fn ($t) => ! str_contains($t, 'UniqueBeta'));
        expect($hasNonBeta)->toBeTrue(
            'After clearFilters, data must show mixed titles. '
            . 'Got: ' . json_encode($debugResult)
        );
    });

    it('displays filter badge when filter is applied', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Apply filter
        $page->script('() => {
            const input = document.querySelector("table thead tr:nth-child(2) td input[type=search]");
            if (input) {
                input.value = "xyz";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }');

        $page->wait(2);

        // Filter badge should contain the filter value
        $page->assertSee('%xyz%');
    });
});
