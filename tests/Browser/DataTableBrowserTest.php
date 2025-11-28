<?php

/**
 * Browser Tests for DataTable Component
 *
 * These tests use Pest's browser testing capabilities with Playwright.
 * They demonstrate how to test the DataTable component in a real browser environment.
 *
 * Requirements:
 * - pestphp/pest-plugin-browser must be installed
 * - npm install playwright@latest && npx playwright install
 *
 * Note: These tests are skipped in package context because they require
 * a running application with routes. When implementing in a real application,
 * remove the skip() calls.
 */
beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);

    for ($i = 1; $i <= 25; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post Title {$i}",
            'content' => "Content for post {$i} with some additional text for searching",
            'price' => $i * 10.50,
            'is_published' => $i % 2 === 0,
        ]);
    }
});

describe('DataTable Browser Rendering', function (): void {
    it('renders the datatable component on the page', function (): void {
        $page = visit('/datatable');

        $page->assertSee('Title')
            ->assertSee('Content')
            ->assertNoJavascriptErrors();
    })->skip('Browser tests require a running application with /datatable route');

    it('displays table headers correctly', function (): void {
        $page = visit('/datatable');

        $page->assertSee('Title')
            ->assertSee('Content')
            ->assertSee('Price')
            ->assertSee('Is Published');
    })->skip('Browser tests require a running application with /datatable route');

    it('displays table data after loading', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->assertSee('Post Title 1')
            ->assertSee('Post Title 2')
            ->assertSee('Post Title 3');
    })->skip('Browser tests require a running application with /datatable route');

    it('has no JavaScript errors on initial load', function (): void {
        $page = visit('/datatable');

        $page->assertNoJavascriptErrors()
            ->assertNoConsoleLogs();
    })->skip('Browser tests require a running application with /datatable route');

    it('renders correctly on mobile viewport', function (): void {
        $page = visit('/datatable')->on()->mobile();

        $page->assertSee('Title')
            ->assertNoJavascriptErrors();
    })->skip('Browser tests require a running application with /datatable route');

    it('renders correctly in dark mode', function (): void {
        $page = visit('/datatable')->inDarkMode();

        $page->assertSee('Title')
            ->assertNoJavascriptErrors();
    })->skip('Browser tests require a running application with /datatable route');

    it('initializes AlpineJS data correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->assertPresent('[x-data]')
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).enabledCols !== undefined')
            ->toBe(true);
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Sorting', function (): void {
    it('can sort by clicking column header', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->assertSee('Post Title 1');
    })->skip('Browser tests require a running application with /datatable route');

    it('toggles sort direction on second click', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->assertSee('Post Title 25');
    })->skip('Browser tests require a running application with /datatable route');

    it('shows sort indicator on sorted column', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->assertPresent('[data-sort="title"][data-sort-direction]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can sort by different columns', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="price"]')
            ->waitForLivewire()
            ->assertPresent('[data-sort="price"][data-sort-direction="asc"]');

        $page->click('[data-sort="created_at"]')
            ->waitForLivewire()
            ->assertPresent('[data-sort="created_at"][data-sort-direction]');
    })->skip('Browser tests require a running application with /datatable route');

    it('updates orderByCol and orderAsc in Alpine data on sort', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).orderByCol')
            ->toBe('title');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Pagination', function (): void {
    it('shows pagination controls when there are multiple pages', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->assertPresent('[data-pagination]')
            ->assertSee('1')
            ->assertSee('2');
    })->skip('Browser tests require a running application with /datatable route');

    it('can navigate to next page', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-page="2"]')
            ->waitForLivewire()
            ->assertUrlContains('page=2');
    })->skip('Browser tests require a running application with /datatable route');

    it('can change items per page', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->select('[data-per-page]', '25')
            ->waitForLivewire();
    })->skip('Browser tests require a running application with /datatable route');

    it('infinite scroll loads more items', function (): void {
        $page = visit('/datatable?infinite=true');

        $page->waitForLivewire()
            ->scroll(0, 1000)
            ->waitForLivewire()
            ->assertSee('Post Title 16');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Selection', function (): void {
    it('can select a single row', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-select-row="1"]')
            ->waitForLivewire()
            ->assertChecked('[data-select-row="1"]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can select multiple rows', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-select-row="1"]')
            ->waitForLivewire()
            ->click('[data-select-row="2"]')
            ->waitForLivewire()
            ->assertChecked('[data-select-row="1"]')
            ->assertChecked('[data-select-row="2"]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can select all rows', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-select-all]')
            ->waitForLivewire()
            ->assertChecked('[data-select-all]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can deselect all rows', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-select-all]')
            ->waitForLivewire()
            ->click('[data-select-all]')
            ->waitForLivewire()
            ->assertNotChecked('[data-select-all]');
    })->skip('Browser tests require a running application with /datatable route');

    it('shows selected count', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-select-row="1"]')
            ->waitForLivewire()
            ->click('[data-select-row="2"]')
            ->waitForLivewire()
            ->assertSee('2 selected');
    })->skip('Browser tests require a running application with /datatable route');

    it('dispatches tall-datatables-selected event on selection', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("window.selectedItems = []; document.addEventListener('tall-datatables-selected', e => window.selectedItems = e.detail)")
            ->click('[data-select-row="1"]')
            ->waitForLivewire()
            ->evaluate('() => window.selectedItems.length')
            ->toBeGreaterThan(0);
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Filtering', function (): void {
    it('shows filter panel when clicking filter button', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->assertVisible('[data-filter-panel]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can add a filter condition', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->assertPresent('[data-filter-row]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can filter by boolean column', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'is_published')
            ->select('[data-filter-operator]', '=')
            ->select('[data-filter-value]', 'true')
            ->click('[data-apply-filters]')
            ->waitForLivewire();
    })->skip('Browser tests require a running application with /datatable route');

    it('can filter by text column with like operator', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'title')
            ->select('[data-filter-operator]', 'like')
            ->type('[data-filter-value]', '%Post Title 1%')
            ->click('[data-apply-filters]')
            ->waitForLivewire()
            ->assertSee('Post Title 1');
    })->skip('Browser tests require a running application with /datatable route');

    it('can remove a filter', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->click('[data-remove-filter]')
            ->waitForLivewire()
            ->assertNotPresent('[data-filter-row]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can clear all filters', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->click('[data-clear-filters]')
            ->waitForLivewire()
            ->assertNotPresent('[data-filter-row]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can add OR filter group', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'title')
            ->type('[data-filter-value]', 'Post')
            ->click('[data-add-or-filter]')
            ->waitForLivewire()
            ->assertPresent('[data-filter-group="1"]');
    })->skip('Browser tests require a running application with /datatable route');

    it('sets default operator to = when filter has value list', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'is_published')
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).newFilter.operator')
            ->toBe('=');
    })->skip('Browser tests require a running application with /datatable route');

    it('hides value input for is null operator', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'title')
            ->select('[data-filter-operator]', 'is null')
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).filterSelectType')
            ->toBe('none');
    })->skip('Browser tests require a running application with /datatable route');

    it('resets filter column when relation changes', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'title')
            ->select('[data-filter-relation]', 'user')
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).newFilter.column')
            ->toBe('');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Search', function (): void {
    it('shows search input', function (): void {
        $page = visit('/datatable');

        $page->assertPresent('[data-search-input]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can search for content', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->type('[data-search-input]', 'Post Title 5')
            ->waitForLivewire()
            ->assertSee('Post Title 5')
            ->assertDontSee('Post Title 1');
    })->skip('Browser tests require a running application with /datatable route');

    it('shows no results message when search returns empty', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->type('[data-search-input]', 'nonexistent content xyz123')
            ->waitForLivewire()
            ->assertSee('No results found');
    })->skip('Browser tests require a running application with /datatable route');

    it('can clear search', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->type('[data-search-input]', 'Post Title 5')
            ->waitForLivewire()
            ->clear('[data-search-input]')
            ->waitForLivewire()
            ->assertSee('Post Title 1');
    })->skip('Browser tests require a running application with /datatable route');

    it('triggers startSearch on search input change', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("window.searchCalled = false; Livewire.hook('message.sent', ({component}) => { if(component.calls.some(c => c.method === 'startSearch')) window.searchCalled = true; })")
            ->type('[data-search-input]', 'test')
            ->waitForLivewire()
            ->evaluate('() => window.searchCalled')
            ->toBeTrue();
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Column Configuration', function (): void {
    it('can toggle column visibility', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-column-settings]')
            ->waitForLivewire()
            ->click('[data-toggle-column="content"]')
            ->waitForLivewire()
            ->assertDontSee('Content');
    })->skip('Browser tests require a running application with /datatable route');

    it('can reorder columns', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-column-settings]')
            ->waitForLivewire()
            ->drag('[data-column-handle="title"]', '[data-column-handle="content"]')
            ->waitForLivewire();
    })->skip('Browser tests require a running application with /datatable route');

    it('persists column configuration', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-column-settings]')
            ->waitForLivewire()
            ->click('[data-toggle-column="content"]')
            ->waitForLivewire();

        $page->navigate('/datatable')
            ->waitForLivewire()
            ->assertDontSee('Content');
    })->skip('Browser tests require a running application with /datatable route');

    it('can reset layout to default', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-column-settings]')
            ->waitForLivewire()
            ->click('[data-toggle-column="content"]')
            ->waitForLivewire()
            ->click('[data-reset-layout]')
            ->waitForLivewire()
            ->assertSee('Content');
    })->skip('Browser tests require a running application with /datatable route');

    it('can toggle sticky columns', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-column-settings]')
            ->waitForLivewire()
            ->click('[data-toggle-sticky="title"]')
            ->waitForLivewire()
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).stickyCols.includes("title")')
            ->toBeTrue();
    })->skip('Browser tests require a running application with /datatable route');

    it('columnSortHandle reorders enabledCols correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.columnSortHandle('content', 0); return data.enabledCols[0]; }")
            ->toBe('content');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Row Actions', function (): void {
    it('can click on a row to navigate', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-row="1"]')
            ->assertUrlContains('/posts/1');
    })->skip('Browser tests require a running application with /datatable route');

    it('shows row action menu on hover', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->hover('[data-row="1"]')
            ->assertVisible('[data-row-actions="1"]');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Export', function (): void {
    it('can open export dialog', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-export-button]')
            ->waitForLivewire()
            ->assertVisible('[data-export-dialog]');
    })->skip('Browser tests require a running application with /datatable route');

    it('can select columns for export', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-export-button]')
            ->waitForLivewire()
            ->click('[data-export-column="title"]')
            ->click('[data-export-column="content"]')
            ->assertChecked('[data-export-column="title"]')
            ->assertChecked('[data-export-column="content"]');
    })->skip('Browser tests require a running application with /datatable route');

    it('loads exportable columns on dialog open', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-export-button]')
            ->waitForLivewire()
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).exportableColumns.length')
            ->toBeGreaterThan(0);
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Formatters', function (): void {
    it('formats money values correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().money(1234.56)')
            ->toContain('1');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats boolean values with checkmark or x icon', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().bool(true)')
            ->toContain('bg-emerald');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats false boolean with red background', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().bool(false)')
            ->toContain('bg-red');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats date values correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().date('2024-01-15')")
            ->toContain('2024');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats datetime values correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().datetime('2024-01-15T10:30:00')")
            ->toContain('2024');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats percentage values correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().percentage(0.5)')
            ->toContain('50');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats email values as mailto links', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().email('test@example.com')")
            ->toContain('mailto:');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats url values as links', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().url('https://example.com')")
            ->toContain('href');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats tel values as tel links', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().tel('+1234567890')")
            ->toContain('tel:');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats image values with img tag', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().image('https://example.com/image.jpg')")
            ->toContain('<img');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats array values as badges', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().array(['one', 'two', 'three'])")
            ->toContain('badge');
    })->skip('Browser tests require a running application with /datatable route');

    it('returns correct input type for different formatters', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().inputType('date')")
            ->toBe('date');

        $page->evaluate("() => formatters().inputType('datetime')")
            ->toBe('datetime-local');

        $page->evaluate("() => formatters().inputType('money')")
            ->toBe('number');

        $page->evaluate("() => formatters().inputType('email')")
            ->toBe('email');
    })->skip('Browser tests require a running application with /datatable route');

    it('guessType correctly identifies different value types', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().guessType(null)')
            ->toBe('null');

        $page->evaluate('() => formatters().guessType([1, 2, 3])')
            ->toBe('array');

        $page->evaluate('() => formatters().guessType({a: 1})')
            ->toBe('object');

        $page->evaluate('() => formatters().guessType(true)')
            ->toBe('boolean');

        $page->evaluate("() => formatters().guessType('test@test.com')")
            ->toBe('email');

        $page->evaluate("() => formatters().guessType('2024-01-15')")
            ->toBe('date');

        $page->evaluate("() => formatters().guessType('2024-01-15T10:30:00')")
            ->toBe('datetime');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats time from milliseconds correctly', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().time(3661000)')
            ->toBe('01:01:01');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats negative time values with minus sign', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().time(-3600000)')
            ->toContain('-');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats colored money with correct colors', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => formatters().coloredMoney(-100)')
            ->toContain('text-red');

        $page->evaluate('() => formatters().coloredMoney(100)')
            ->toContain('text-emerald');
    })->skip('Browser tests require a running application with /datatable route');

    it('formats badge with custom colors', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => formatters().badge('active', {active: 'green', inactive: 'red'})")
            ->toContain('bg-green');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Text Filter Parsing', function (): void {
    it('parses operator from text filter value', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.textFilter = {title: '>=100'}; data.parseFilter(); return data.filters[0][0].operator; }")
            ->toBe('>=');
    })->skip('Browser tests require a running application with /datatable route');

    it('wraps text values in like pattern', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.textFilter = {title: 'test'}; data.parseFilter(); return data.filters[0][0].value; }")
            ->toBe('%test%');
    })->skip('Browser tests require a running application with /datatable route');

    it('preserves percent signs in user input', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.textFilter = {title: '%custom%'}; data.parseFilter(); return data.filters[0][0].value; }")
            ->toBe('%custom%');
    })->skip('Browser tests require a running application with /datatable route');

    it('detects date values and uses equals operator', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.formatters = {created_at: 'date'}; data.textFilter = {created_at: '2024-01-15'}; data.parseFilter(); return data.filters[0][0].operator; }")
            ->toBe('=');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Filter Badge Display', function (): void {
    it('displays filter badge with column label', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.colLabels = {title: 'Title'}; data.operatorLabels = {'=': 'equals'}; return data.filterBadge({column: 'title', operator: '=', value: 'test'}); }")
            ->toContain('Title');
    })->skip('Browser tests require a running application with /datatable route');

    it('displays operator label in filter badge', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.colLabels = {}; data.operatorLabels = {'>=': 'greater than or equal'}; return data.filterBadge({column: 'price', operator: '>=', value: '100'}); }")
            ->toContain('greater than or equal');
    })->skip('Browser tests require a running application with /datatable route');

    it('uses value list label when available', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); data.filterValueLists = {status: [{value: 'active', label: 'Active'}]}; return data.filterBadge({column: 'status', operator: '=', value: 'active'}); }")
            ->toContain('Active');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Saved Filters', function (): void {
    it('can save current filter configuration', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-add-filter]')
            ->waitForLivewire()
            ->select('[data-filter-column]', 'title')
            ->type('[data-filter-value]', 'test')
            ->type('[data-filter-name]', 'My Test Filter')
            ->click('[data-save-filter]')
            ->waitForLivewire()
            ->assertSee('My Test Filter');
    })->skip('Browser tests require a running application with /datatable route');

    it('can load saved filter', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->click('[data-saved-filter="1"]')
            ->waitForLivewire()
            ->assertPresent('[data-filter-row]');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Aggregations', function (): void {
    it('can enable column aggregation', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-aggregate="price"]')
            ->waitForLivewire()
            ->evaluate('() => Alpine.$data(document.querySelector("[x-data]")).aggregatableCols.includes("price")')
            ->toBeTrue();
    })->skip('Browser tests require a running application with /datatable route');

    it('triggers applyAggregations on aggregatableCols change', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("window.aggCalled = false; Livewire.hook('message.sent', ({component}) => { if(component.calls.some(c => c.method === 'applyAggregations')) window.aggCalled = true; })")
            ->click('[data-aggregate="price"]')
            ->waitForLivewire()
            ->evaluate('() => window.aggCalled')
            ->toBeTrue();
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Data Handling', function (): void {
    it('getData returns data array from paginated response', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate('() => Array.isArray(Alpine.$data(document.querySelector("[x-data]")).getData())')
            ->toBeTrue();
    })->skip('Browser tests require a running application with /datatable route');

    it('searchable filters data by search string', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); const filtered = data.searchable({a: 'apple', b: 'banana'}, 'app'); return Object.keys(filtered).length; }")
            ->toBe(1);
    })->skip('Browser tests require a running application with /datatable route');

    it('searchable returns all data when search is empty', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); const filtered = data.searchable({a: 'apple', b: 'banana'}, ''); return Object.keys(filtered).length; }")
            ->toBe(2);
    })->skip('Browser tests require a running application with /datatable route');

    it('searchable works with arrays', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => { const data = Alpine.\$data(document.querySelector('[x-data]')); const filtered = data.searchable(['apple', 'banana', 'apricot'], 'ap'); return filtered.length; }")
            ->toBe(2);
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Echo Integration', function (): void {
    it('subscribes to Echo channels when available', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->evaluate("() => typeof window.Echo !== 'undefined'");
        // Test depends on Echo being configured
    })->skip('Browser tests require a running application with /datatable route and Echo configuration');
});

describe('DataTable Browser Performance', function (): void {
    it('loads within acceptable time', function (): void {
        $startTime = microtime(true);

        $page = visit('/datatable');
        $page->waitForLivewire();

        $loadTime = microtime(true) - $startTime;

        expect($loadTime)->toBeLessThan(5.0);
    })->skip('Browser tests require a running application with /datatable route');

    it('handles rapid pagination without errors', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-page="2"]')
            ->click('[data-page="3"]')
            ->click('[data-page="1"]')
            ->waitForLivewire()
            ->assertNoJavascriptErrors();
    })->skip('Browser tests require a running application with /datatable route');

    it('handles rapid sorting without errors', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-sort="title"]')
            ->click('[data-sort="price"]')
            ->click('[data-sort="title"]')
            ->waitForLivewire()
            ->assertNoJavascriptErrors();
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Accessibility', function (): void {
    it('has no accessibility issues', function (): void {
        $page = visit('/datatable');

        $page->assertNoAccessibilityIssues();
    })->skip('Browser tests require a running application with /datatable route');

    it('can be navigated with keyboard', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->keys('[data-search-input]', ['Tab'])
            ->keys(['Enter'])
            ->waitForLivewire();
    })->skip('Browser tests require a running application with /datatable route');

    it('has proper ARIA labels', function (): void {
        $page = visit('/datatable');

        $page->assertAriaAttribute('[data-table]', 'label', 'Data table')
            ->assertAriaAttribute('[data-search-input]', 'label', 'Search');
    })->skip('Browser tests require a running application with /datatable route');

    it('has focusable elements in logical order', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->assertPresent('[tabindex]');
    })->skip('Browser tests require a running application with /datatable route');
});

describe('DataTable Browser Visual Regression', function (): void {
    it('matches screenshot on desktop', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->assertScreenshotMatches('datatable-desktop');
    })->skip('Browser tests require a running application with /datatable route and baseline screenshots');

    it('matches screenshot on mobile', function (): void {
        $page = visit('/datatable')->on()->mobile();

        $page->waitForLivewire()
            ->assertScreenshotMatches('datatable-mobile');
    })->skip('Browser tests require a running application with /datatable route and baseline screenshots');

    it('matches screenshot in dark mode', function (): void {
        $page = visit('/datatable')->inDarkMode();

        $page->waitForLivewire()
            ->assertScreenshotMatches('datatable-dark');
    })->skip('Browser tests require a running application with /datatable route and baseline screenshots');

    it('matches screenshot with filter panel open', function (): void {
        $page = visit('/datatable');

        $page->waitForLivewire()
            ->click('[data-toggle-filters]')
            ->waitForLivewire()
            ->assertScreenshotMatches('datatable-filters');
    })->skip('Browser tests require a running application with /datatable route and baseline screenshots');
});
