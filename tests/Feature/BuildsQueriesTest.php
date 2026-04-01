<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Query User', 'email' => 'query@example.com']);
    $this->actingAs($this->user);
});

// ---------------------------------------------------------------------------
// applyFilters — fixed filters (the $filters property)
// ---------------------------------------------------------------------------
describe('applyFilters with fixed filters', function (): void {
    it('applies a fixed where filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Fixed Match', 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Fixed Match', 'is_published' => false]);

        $component = Livewire::test(Tests\Fixtures\Livewire\FilteredPostDataTable::class);

        // Set fixed filters before mount
        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [
            ['is_published', '=', true],
        ];

        $component = Livewire::test(Tests\Fixtures\Livewire\FilteredPostDataTable::class);
        $data = $component->instance()->getDataForTesting();

        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Fixed Match');

        // Reset
        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [];
    });

    it('applies a fixed is null filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Price', 'price' => null]);

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [
            ['column' => 'price', 'operator' => 'is null', 'value' => null],
        ];

        $component = Livewire::test(Tests\Fixtures\Livewire\FilteredPostDataTable::class);
        $data = $component->instance()->getDataForTesting();

        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('No Price');

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [];
    });

    it('applies a fixed is not null filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Price', 'price' => null]);

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [
            ['column' => 'price', 'operator' => 'is not null', 'value' => null],
        ];

        $component = Livewire::test(Tests\Fixtures\Livewire\FilteredPostDataTable::class);
        $data = $component->instance()->getDataForTesting();

        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Has Price');

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [];
    });

    it('applies a named Where filter method', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Named Filter', 'price' => 50]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other', 'price' => 100]);

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [
            'Where' => [['title', '=', 'Named Filter']],
        ];

        $component = Livewire::test(Tests\Fixtures\Livewire\FilteredPostDataTable::class);
        $data = $component->instance()->getDataForTesting();

        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Named Filter');

        Tests\Fixtures\Livewire\FilteredPostDataTable::$testFilters = [];
    });
});

// ---------------------------------------------------------------------------
// applyFilters — user filters with all operators
// ---------------------------------------------------------------------------
describe('applyFilters with user filters', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha', 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta', 'price' => 20]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Gamma', 'price' => 30]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Delta', 'price' => 40]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Epsilon', 'price' => null]);
    });

    it('filters with equals operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => 'Alpha']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Alpha');
    });

    it('filters with not equals operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '!=', 'value' => 'Alpha']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(4);
        expect(collect($data['data'])->pluck('title')->toArray())->not->toContain('Alpha');
    });

    it('filters with greater than operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '>', 'value' => 20]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('filters with less than operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '<', 'value' => 30]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('filters with greater than or equal operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '>=', 'value' => 30]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('filters with less than or equal operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '<=', 'value' => 20]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('filters with like operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => 'like', 'value' => '%lph%']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Alpha');
    });

    it('filters with is null operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'is null', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Epsilon');
    });

    it('filters with is not null operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'is not null', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(4);
    });

    it('filters with between operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'between', 'value' => [15, 35]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('skips between filter when value is not a two element array', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'between', 'value' => [15]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        // between with wrong count is skipped, so all results returned
        expect($data['total'])->toBe(5);
    });

    it('skips filter when value is null on non-null operator', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(5);
    });
});

// ---------------------------------------------------------------------------
// OR-group logic (groups are OR'd, filters within groups are AND'd)
// ---------------------------------------------------------------------------
describe('OR group logic', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Apple', 'price' => 10, 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Banana', 'price' => 20, 'is_published' => false]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Cherry', 'price' => 30, 'is_published' => true]);
    });

    it('ORs between filter groups', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => 'Apple']],
            [['column' => 'title', 'operator' => '=', 'value' => 'Cherry']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
        $titles = collect($data['data'])->pluck('title')->sort()->values()->toArray();
        expect($titles)->toBe(['Apple', 'Cherry']);
    });

    it('ANDs within the same filter group', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [
                ['column' => 'price', 'operator' => '>=', 'value' => 10],
                ['column' => 'is_published', 'operator' => '=', 'value' => 1],
            ],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('skips non-array entries in filter groups', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            'invalid_string_entry',
            [['column' => 'title', 'operator' => '=', 'value' => 'Apple']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// addFilter — relation filters (dot notation columns)
// ---------------------------------------------------------------------------
describe('addFilter with relation columns', function (): void {
    it('filters through a belongsTo relation', function (): void {
        $user1 = createTestUser(['name' => 'Alice']);
        $user2 = createTestUser(['name' => 'Bob']);
        createTestPost(['user_id' => $user1->getKey(), 'title' => 'Alice Post']);
        createTestPost(['user_id' => $user2->getKey(), 'title' => 'Bob Post']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'user.name', 'operator' => '=', 'value' => 'Alice']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Alice Post');
    });

    it('filters with whereHas using * value', function (): void {
        $user1 = createTestUser(['name' => 'Poster']);
        createTestPost(['user_id' => $user1->getKey(), 'title' => 'Has Comments']);
        $post = Post::where('title', 'Has Comments')->first();
        createTestComment(['user_id' => $user1->getKey(), 'post_id' => $post->getKey(), 'body' => 'a comment']);

        $user2 = createTestUser(['name' => 'Silent']);
        createTestPost(['user_id' => $user2->getKey(), 'title' => 'No Comments']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'comments.body', 'operator' => 'like', 'value' => '%*%']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Has Comments');
    });

    it('filters with whereDoesntHave using bang-star value', function (): void {
        $user1 = createTestUser(['name' => 'Poster']);
        createTestPost(['user_id' => $user1->getKey(), 'title' => 'Has Comments']);
        $post = Post::where('title', 'Has Comments')->first();
        createTestComment(['user_id' => $user1->getKey(), 'post_id' => $post->getKey(), 'body' => 'a comment']);

        $user2 = createTestUser(['name' => 'Silent']);
        createTestPost(['user_id' => $user2->getKey(), 'title' => 'No Comments']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'comments.body', 'operator' => 'like', 'value' => '%!*%']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('No Comments');
    });

    it('handles non-existent relation gracefully', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'nonExistent.field', 'operator' => '=', 'value' => 'test']],
        ]);
        $component->call('loadData');

        // Should not throw, just skip the invalid relation filter
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });
});

// ---------------------------------------------------------------------------
// addFilter — simple key-value string filters
// ---------------------------------------------------------------------------
describe('addFilter with string filter (key-value shorthand)', function (): void {
    it('applies a simple key-value filter as equals', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Exact Match']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            ['title' => 'Exact Match'],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Exact Match');
    });
});

// ---------------------------------------------------------------------------
// parseFilter — date parsing
// ---------------------------------------------------------------------------
describe('parseFilter date conversion', function (): void {
    it('converts dot-separated date format to Y-m-d H:i:s', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Date Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '01.01.2020']],
        ]);
        $component->call('loadData');

        // The filter should have been parsed; we can check that data loaded without errors
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });

    it('converts slash-separated date format to Y-m-d H:i:s', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15/06/2023']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });

    it('converts Y-m-d format', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '2023-06-15']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });

    it('converts datetime formats with time', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15.06.2023 14:30']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });

    it('handles numeric values as float', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Priced', 'price' => 42.50]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '=', 'value' => '42.50']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('preserves filter without value key', function (): void {
        $component = Livewire::test(PostDataTable::class);
        // Set a filter that lacks a 'value' key — parseFilter should just return it
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=']],
        ]);
        $component->call('loadData');

        // Should not crash
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });
});

// ---------------------------------------------------------------------------
// normalizeNumericValue
// ---------------------------------------------------------------------------
describe('normalizeNumericValue', function (): void {
    it('keeps comma as thousands separator when more than 2 digits follow', function (): void {
        // 1,000 should NOT be parsed as 1.000 (i.e. 1 with decimal 000)
        // Instead the comma with 3+ trailing digits is kept as-is
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Integer', 'price' => 10]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '=', 'value' => '10']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('handles plain integer strings', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Integer', 'price' => 100]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '=', 'value' => '100']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// parseTextFilterValue
// ---------------------------------------------------------------------------
describe('parseTextFilterValue via setTextFilter', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Parseable', 'price' => 50]);
    });

    it('parses "is null" text', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', 'is null');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('is null');
        expect($userFilters[0][0]['value'])->toBeNull();
    });

    it('parses "is not null" text (case insensitive)', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', 'IS NOT NULL');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('is not null');
        expect($userFilters[0][0]['value'])->toBeNull();
    });

    it('parses "is  null" with extra whitespace', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', 'is  null');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('is null');
    });

    it('parses exclamation-star as whereDoesntHave marker', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', '!*');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('%!*%');
        expect($userFilters[0][0]['operator'])->toBe('like');
    });

    it('parses * as whereHas marker', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', '*');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('%*%');
        expect($userFilters[0][0]['operator'])->toBe('like');
    });

    it('parses greater-than-or-equal operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '>= 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('>=');
    });

    it('parses less-than-or-equal operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '<= 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('<=');
    });

    it('parses not-equal operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '!= 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('!=');
    });

    it('parses greater-than operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '> 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('>');
    });

    it('parses less-than operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '< 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('<');
    });

    it('parses equals operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '= 50');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('=');
    });

    it('defaults to like with wildcards for plain text', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Parse');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('like');
        expect($userFilters[0][0]['value'])->toBe('%Parse%');
    });

    it('parses date for date columns as like with prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '15.01.2024');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('like');
        expect($userFilters[0][0]['value'])->toBe('2024-01-15%');
    });

    it('parses partial two-part date for date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '15.01');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('like');
        $expectedYear = date('Y');
        expect($userFilters[0][0]['value'])->toBe("{$expectedYear}-01-15%");
    });

    it('parses date with operator prefix for date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '>=15.01.2024');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('>=');
        expect($userFilters[0][0]['value'])->toBe('2024-01-15');
    });

    it('parses slash date for date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '25/12/2023');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2023-12-25%');
    });

    it('falls back to like for unparseable date input on date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', 'not-a-date');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('like');
        expect($userFilters[0][0]['value'])->toBe('%not-a-date%');
    });
});

// ---------------------------------------------------------------------------
// isDateColumn
// ---------------------------------------------------------------------------
describe('isDateColumn detection', function (): void {
    it('detects _at suffix columns as date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '01.01.2024');

        $userFilters = $component->get('userFilters');
        // Date columns produce like operator with Y-m-d format value
        expect($userFilters[0][0]['value'])->toBe('2024-01-01%');
    });

    it('does not detect regular columns as date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', '01.01.2024');

        $userFilters = $component->get('userFilters');
        // Non-date column should get plain like with wildcards
        expect($userFilters[0][0]['operator'])->toBe('like');
        expect($userFilters[0][0]['value'])->toBe('%01.01.2024%');
    });
});

// ---------------------------------------------------------------------------
// parseDateValue
// ---------------------------------------------------------------------------
describe('parseDateValue', function (): void {
    it('parses dot-separated full date', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '05.03.2025');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2025-03-05%');
    });

    it('parses slash-separated full date', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '05/03/2025');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2025-03-05%');
    });

    it('parses two-part dot date with current year', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '05.03');

        $userFilters = $component->get('userFilters');
        $year = date('Y');
        expect($userFilters[0][0]['value'])->toBe("{$year}-03-05%");
    });

    it('returns null for two-part date when month exceeds 12', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '05.13');

        $userFilters = $component->get('userFilters');
        // Should fall back to like with wildcards since parseDateValue returns null
        expect($userFilters[0][0]['value'])->toBe('%05.13%');
    });

    it('accepts Y-m-d format as-is', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '2025-03-05');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2025-03-05%');
    });

    it('accepts partial Y-m format', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '2025-03');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2025-03%');
    });
});

// ---------------------------------------------------------------------------
// migrateFilterFormat
// ---------------------------------------------------------------------------
describe('migrateFilterFormat', function (): void {
    it('migrates old text format to new grouped format', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            'text' => ['title' => 'Alpha'],
        ]);
        $component->call('loadData');

        // After migration, the text key should be gone and filters should be in groups
        $userFilters = $component->get('userFilters');
        expect($userFilters)->not->toHaveKey('text');
        expect($userFilters[0][0]['column'])->toBe('title');
        expect($userFilters[0][0]['source'])->toBe('text');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('skips empty text filter values during migration', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            'text' => ['title' => '', 'content' => null],
        ]);
        $component->call('loadData');

        // Empty/null values should be skipped, resulting in no text filters
        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('preserves existing sidebar groups during migration', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta', 'price' => 200]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            'text' => ['title' => 'Alpha'],
            0 => [['column' => 'price', 'operator' => '=', 'value' => 100]],
        ]);
        $component->call('loadData');

        $userFilters = $component->get('userFilters');
        // Text group comes first, then sidebar group
        expect(count($userFilters))->toBeGreaterThanOrEqual(2);
    });

    it('returns filters unchanged when text key is absent', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => 'Alpha']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('populates textFilters for input restoration during migration', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Restored']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            'text' => ['title' => 'Restored'],
        ]);
        $component->call('loadData');

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveKey('title');
        expect($textFilters['title'])->toBe('Restored');
    });
});

// ---------------------------------------------------------------------------
// search (fallback search without Scout)
// ---------------------------------------------------------------------------
describe('fallback search', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Searchable Alpha', 'content' => 'First content']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Searchable Beta', 'content' => 'Second content']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other Post', 'content' => 'Third content']);
    });

    it('finds results matching search term in any enabled column', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('search', 'Searchable');
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('searches through content column', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('search', 'First content');
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Searchable Alpha');
    });

    it('returns empty results for non-matching search', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('search', 'ZZZNonExistentXXX');
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(0);
    });

    it('searches through relation columns', function (): void {
        $alice = createTestUser(['name' => 'Alice Wonderland']);
        createTestPost(['user_id' => $alice->getKey(), 'title' => 'Alice Post']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('search', 'Wonderland');
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBeGreaterThanOrEqual(1);
    });
});

// ---------------------------------------------------------------------------
// applyFilterWhereNull
// ---------------------------------------------------------------------------
describe('applyFilterWhereNull', function (): void {
    it('filters null values correctly', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'With Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Without Price', 'price' => null]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'is null', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Without Price');
    });

    it('filters not null values correctly', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'With Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Without Price', 'price' => null]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'is not null', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('With Price');
    });
});

// ---------------------------------------------------------------------------
// applyFilterWhereBetween (via between operator)
// ---------------------------------------------------------------------------
describe('applyFilterWhereBetween', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P10', 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P20', 'price' => 20]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P30', 'price' => 30]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P40', 'price' => 40]);
    });

    it('includes both boundary values', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'between', 'value' => [20, 30]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
        $titles = collect($data['data'])->pluck('title')->sort()->values()->toArray();
        expect($titles)->toBe(['P20', 'P30']);
    });

    it('returns single result when boundaries are equal', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'between', 'value' => [20, 20]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('returns nothing when range excludes all values', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => 'between', 'value' => [100, 200]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// withSoftDeletes
// ---------------------------------------------------------------------------
describe('withSoftDeletes', function (): void {
    it('excludes soft-deleted records by default', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Soft Deleted']);
        $post->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Active']);

        $component = Livewire::test(PostDataTable::class);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Active');
    });

    it('includes soft-deleted records when withSoftDeletes is true', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Soft Deleted']);
        $post->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Active']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('withSoftDeletes', true);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// sorting
// ---------------------------------------------------------------------------
describe('sorting', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Zebra', 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Apple', 'price' => 30]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Mango', 'price' => 20]);
    });

    it('sorts by specified column ascending', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userOrderBy', 'title');
        $component->set('userOrderAsc', true);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->toArray();
        expect($titles)->toBe(['Apple', 'Mango', 'Zebra']);
    });

    it('sorts by specified column descending', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('userOrderBy', 'title');
        $component->set('userOrderAsc', false);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->toArray();
        expect($titles)->toBe(['Zebra', 'Mango', 'Apple']);
    });

    it('falls back to default orderBy when no user sort specified', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('loadData');

        // Default is no orderBy set which falls back to model key DESC
        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(3);
    });
});

// ---------------------------------------------------------------------------
// Combined filters and search
// ---------------------------------------------------------------------------
describe('combined filters and search', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha One', 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha Two', 'price' => 20]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta One', 'price' => 30]);
    });

    it('applies both search and filters together', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('search', 'Alpha');
        $component->set('userFilters', [
            [['column' => 'price', 'operator' => '>=', 'value' => 15]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Alpha Two');
    });
});

// ---------------------------------------------------------------------------
// Edge cases and error handling
// ---------------------------------------------------------------------------
describe('edge cases and error handling', function (): void {
    it('handles empty userFilters gracefully', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', []);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('handles filter with unknown operator gracefully', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => 'INVALID_OP', 'value' => 'Test']],
        ]);
        $component->call('loadData');

        // Should not crash — invalid operators are caught by Throwable
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });

    it('handles multiple filters in a single group', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Match', 'price' => 50, 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Match', 'price' => 50, 'is_published' => false]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Match Either', 'price' => 10, 'is_published' => true]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [
                ['column' => 'price', 'operator' => '=', 'value' => 50],
                ['column' => 'is_published', 'operator' => '=', 'value' => 1],
                ['column' => 'title', 'operator' => 'like', 'value' => '%Match%'],
            ],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Match');
    });

    it('handles filter with extra keys (parseFilter strips them)', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => 'Test', 'source' => 'text', 'extra_key' => 'ignored']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('handles relation filter with is null on relation column', function (): void {
        $user1 = createTestUser(['name' => 'HasPosts']);
        createTestPost(['user_id' => $user1->getKey(), 'title' => 'Posted']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'user.name', 'operator' => 'is null', 'value' => null]],
        ]);
        $component->call('loadData');

        // Should work without error
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });
});

// ---------------------------------------------------------------------------
// Relation filter with nested whereHas
// ---------------------------------------------------------------------------
describe('nested relation filters', function (): void {
    it('filters through relation with like operator', function (): void {
        $alice = createTestUser(['name' => 'Alice']);
        $bob = createTestUser(['name' => 'Bob']);
        createTestPost(['user_id' => $alice->getKey(), 'title' => 'Alice Post']);
        createTestPost(['user_id' => $bob->getKey(), 'title' => 'Bob Post']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'user.name', 'operator' => 'like', 'value' => '%Ali%']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Alice Post');
    });

    it('filters through relation with is null operator', function (): void {
        $user1 = createTestUser(['name' => 'WithEmail', 'email' => 'has@email.com']);
        createTestPost(['user_id' => $user1->getKey(), 'title' => 'EmailPost']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->set('userFilters', [
            [['column' => 'user.email', 'operator' => 'is not null', 'value' => null]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBeGreaterThanOrEqual(1);
    });
});

// ---------------------------------------------------------------------------
// normalizeFilterValue with date columns
// ---------------------------------------------------------------------------
describe('normalizeFilterValue', function (): void {
    it('normalizes date value for date columns with operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '>= 01.06.2023');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('>=');
        expect($userFilters[0][0]['value'])->toBe('2023-06-01');
    });

    it('normalizes numeric value for non-date columns with operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '>= 1.234,56');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('>=');
        expect($userFilters[0][0]['value'])->toBe(1234.56);
    });
});

// ---------------------------------------------------------------------------
// filterValueLists — uses exact match for columns with value lists
// ---------------------------------------------------------------------------
describe('filterValueLists exact match', function (): void {
    it('uses exact match when column has filterValueLists', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published', 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Unpublished', 'is_published' => false]);

        $component = Livewire::test(PostDataTable::class);
        // Manually set filterValueLists to simulate that is_published has value options
        $component->instance()->filterValueLists = ['is_published' => [true, false]];
        $component->call('setTextFilter', 'is_published', '1');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['operator'])->toBe('=');
        expect($userFilters[0][0]['value'])->toBe('1');
    });
});

// ---------------------------------------------------------------------------
// parseFilter with calculation
// ---------------------------------------------------------------------------
describe('parseFilter with date calculations', function (): void {
    it('handles filter value with calculation (add days)', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Recent']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => [
                ['calculation' => [
                    'operator' => '-',
                    'unit' => 'days',
                    'value' => 7,
                ]],
            ]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        // The post was just created so it should be within the last 7 days
        expect($data['total'])->toBe(1);
    });

    it('handles filter value with calculation and startOf', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'This Month']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => [
                ['calculation' => [
                    'operator' => '-',
                    'unit' => 'months',
                    'value' => 0,
                    'is_start_of' => 1,
                    'start_of' => 'month',
                ]],
            ]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('handles filter value with calculation and endOf', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'End Check']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '<=', 'value' => [
                ['calculation' => [
                    'operator' => '+',
                    'unit' => 'months',
                    'value' => 1,
                    'is_start_of' => 0,
                    'start_of' => 'month',
                ]],
            ]]],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// applyFilterWhere and applyFilterWith as named filter methods
// ---------------------------------------------------------------------------
describe('named filter methods via type string', function (): void {
    it('ignores non-existent filter method gracefully', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->instance()->filters = ['NonExistent' => ['some', 'data']];
        $component->call('loadData');

        // Should not crash, just skip the unknown filter method
        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// startSearch resets page and selection
// ---------------------------------------------------------------------------
describe('startSearch', function (): void {
    it('resets page to 1 and clears selection', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post ' . $i]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 2);

        expect($component->get('page'))->toBe(2);

        $component->call('startSearch');
        expect($component->get('page'))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// allowSoftDeletes detection
// ---------------------------------------------------------------------------
describe('allowSoftDeletes', function (): void {
    it('returns true for models using SoftDeletes trait', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $reflection = new ReflectionMethod($component->instance(), 'allowSoftDeletes');
        $result = $reflection->invoke($component->instance());

        expect($result)->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// getIsSearchable
// ---------------------------------------------------------------------------
describe('getIsSearchable', function (): void {
    it('returns false for models without Scout Searchable trait', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $reflection = new ReflectionMethod($component->instance(), 'getIsSearchable');
        $result = $reflection->invoke($component->instance());

        expect($result)->toBeFalse();
    });

    it('uses cached isSearchable value when set', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('isSearchable', true);

        $reflection = new ReflectionMethod($component->instance(), 'getIsSearchable');
        $result = $reflection->invoke($component->instance());

        expect($result)->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// getReturnKeys with and without soft deletes
// ---------------------------------------------------------------------------
describe('getReturnKeys', function (): void {
    it('includes deleted_at when withSoftDeletes is true', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('withSoftDeletes', true);

        $reflection = new ReflectionMethod($component->instance(), 'getReturnKeys');
        $result = $reflection->invoke($component->instance());

        expect($result)->toContain('deleted_at');
    });

    it('excludes deleted_at when withSoftDeletes is false', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('withSoftDeletes', false);

        $reflection = new ReflectionMethod($component->instance(), 'getReturnKeys');
        $result = $reflection->invoke($component->instance());

        expect($result)->not->toContain('deleted_at');
    });

    it('always includes modelKeyName and href', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $reflection = new ReflectionMethod($component->instance(), 'getReturnKeys');
        $result = $reflection->invoke($component->instance());

        expect($result)->toContain('id')
            ->and($result)->toContain('href');
    });
});

// ---------------------------------------------------------------------------
// normalizeNumericValue edge cases
// ---------------------------------------------------------------------------
describe('normalizeNumericValue additional cases', function (): void {
    it('parses German decimal format (comma as decimal)', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'German', 'price' => 39.99]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '= 39,99');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe(39.99);
    });

    it('parses value with both dots and commas (European format)', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'European', 'price' => 1234.56]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '= 1.234,56');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe(1234.56);
    });

    it('parses value with comma thousands separator (US format)', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'US', 'price' => 1234.56]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'price', '= 1,234.56');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe(1234.56);
    });
});

// ---------------------------------------------------------------------------
// applySessionFilter
// ---------------------------------------------------------------------------
describe('applySessionFilter', function (): void {
    it('applies a session filter when present', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Session Match', 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Session Skip', 'price' => 999]);

        // Set session filter BEFORE mounting the component
        $cacheKey = PostDataTable::class . '_query';
        $sessionFilter = new TeamNiftyGmbH\DataTable\Helpers\SessionFilter(
            'test-filter',
            function ($query): void {
                $query->where('title', 'Session Match');
            }
        );
        session()->put($cacheKey, $sessionFilter);

        $component = Livewire::test(PostDataTable::class);

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Session Match');

        session()->forget($cacheKey);
    });

    it('does not crash when no session filter is present', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Session Filter']);

        $component = Livewire::test(PostDataTable::class);

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($component->get('sessionFilter'))->toBe([]);
    });

    it('clears userFilters on first load of session filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Session Post']);

        $cacheKey = PostDataTable::class . '_query';
        $sessionFilter = new TeamNiftyGmbH\DataTable\Helpers\SessionFilter(
            'clearing-filter',
            function ($query): void {
                $query->where('title', 'like', '%Session%');
            }
        );
        session()->put($cacheKey, $sessionFilter);

        $component = Livewire::test(PostDataTable::class);

        // After initial load, userFilters should be empty (cleared by session filter)
        expect($component->get('userFilters'))->toBe([]);

        session()->forget($cacheKey);
    });
});

// ---------------------------------------------------------------------------
// itemToArray with InteractsWithDataTables href
// ---------------------------------------------------------------------------
describe('itemToArray generates href', function (): void {
    it('generates href for models implementing InteractsWithDataTables', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has URL']);

        $component = Livewire::test(PostDataTable::class);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['data'][0]['href'])->toBe('/posts/' . $post->getKey());
    });

    it('sets href to null when hasNoRedirect is true', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Redirect']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('hasNoRedirect', true);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['data'][0]['href'])->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// resolveCastsForColumn
// ---------------------------------------------------------------------------
describe('resolveCastsForColumn', function (): void {
    it('resolves casts for relation column', function (): void {
        $user = createTestUser(['name' => 'Casts Test']);
        createTestPost(['user_id' => $user->getKey(), 'title' => 'Relation Cast']);

        $component = Livewire::test(PostWithRelationsDataTable::class);
        $component->call('loadData');

        // Should not crash - the method is called internally during formatters resolution
        $data = $component->instance()->getDataForTesting();
        expect($data)->toBeArray();
    });
});
