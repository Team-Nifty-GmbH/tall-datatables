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
