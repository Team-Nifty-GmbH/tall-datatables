<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    // Create 15 published posts
    for ($i = 0; $i < 15; $i++) {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'title' => 'Published ' . $i]);
    }
    // Create 10 unpublished posts
    for ($i = 0; $i < 10; $i++) {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'title' => 'Unpublished ' . $i]);
    }
});

describe('Grouping Initialization', function (): void {
    it('has groupBy property', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->assertSet('groupBy', null);
    });

    it('has expandedGroups property', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->assertSet('expandedGroups', []);
    });

    it('has groupPages property', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->assertSet('groupPages', []);
    });

    it('has groupPerPage property with default value', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->assertSet('groupPerPage', 5);
    });

    it('includes groupable in config', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $config = $component->instance()->getConfig();

        expect($config)->toHaveKey('groupable')
            ->and($config['groupable'])->toBeArray();
    });

    it('groupable excludes relation columns', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $config = $component->instance()->getConfig();
        $groupable = $config['groupable'];

        expect($groupable)->toBeArray();

        foreach ($groupable as $col) {
            expect($col)->not->toContain('.');
        }
    });
});

describe('Setting GroupBy', function (): void {
    it('can set groupBy column', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $component->assertSet('groupBy', 'is_published');
    });

    it('resets groupPages when changing groupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('groupPages', ['true' => 2]);
        $component->call('setGroupBy', 'is_published');

        $component->assertSet('groupPages', []);
    });

    it('resets expandedGroups when changing groupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('expandedGroups', ['true']);
        $component->call('setGroupBy', 'is_published');

        $component->assertSet('expandedGroups', []);
    });

    it('can clear groupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('setGroupBy', null);

        $component->assertSet('groupBy', null);
    });
});

describe('Grouped Data Loading', function (): void {
    it('returns groups structure when grouped', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        expect($data)->toHaveKey('groups')
            ->and($data['groups'])->toBeArray();
    });

    it('each group has required keys', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');
        $group = $data['groups'][0];

        expect($group)->toHaveKey('key')
            ->and($group)->toHaveKey('value')
            ->and($group)->toHaveKey('label')
            ->and($group)->toHaveKey('count')
            ->and($group)->toHaveKey('aggregates')
            ->and($group)->toHaveKey('data')
            ->and($group)->toHaveKey('pagination');
    });

    it('group pagination has required keys when expanded', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        // Expand the first group to get pagination data
        $component->call('toggleGroup', '__false__');

        $data = $component->get('data');
        $expandedGroup = collect($data['groups'])->firstWhere('key', '__false__');
        $pagination = $expandedGroup['pagination'];

        expect($pagination)->toHaveKey('current_page')
            ->and($pagination)->toHaveKey('last_page')
            ->and($pagination)->toHaveKey('per_page')
            ->and($pagination)->toHaveKey('total')
            ->and($pagination)->toHaveKey('from')
            ->and($pagination)->toHaveKey('to');
    });

    it('group pagination is null when not expanded', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');
        $collapsedGroup = $data['groups'][0];

        expect($collapsedGroup['pagination'])->toBeNull();
    });

    it('correctly groups by boolean column', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');
        $groups = $data['groups'];

        expect($groups)->toHaveCount(2);

        $trueGroup = collect($groups)->firstWhere('key', '__true__');
        $falseGroup = collect($groups)->firstWhere('key', '__false__');

        expect($trueGroup['count'])->toBe(15)
            ->and($falseGroup['count'])->toBe(10);
    });

    it('limits group data to groupPerPage when expanded', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__false__');

        $data = $component->get('data');
        $groups = $data['groups'];

        foreach ($groups as $group) {
            if ($group['expanded']) {
                expect(count($group['data']))->toBeLessThanOrEqual(5);
            }
        }
    });

    it('respects custom groupPerPage when expanded', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('groupPerPage', 3);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        $data = $component->get('data');
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect(count($trueGroup['data']))->toBeLessThanOrEqual(3);
    });

    it('collapsed groups have empty data array', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        foreach ($data['groups'] as $group) {
            expect($group['data'])->toBeEmpty();
        }
    });

    it('returns total count across all groups', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        expect($data['total'])->toBe(25);
    });
});

describe('Group Toggle', function (): void {
    it('can expand a group', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        $expandedGroups = $component->get('expandedGroups');

        expect($expandedGroups)->toContain('__true__');
    });

    it('can collapse an expanded group', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__true__');

        $expandedGroups = $component->get('expandedGroups');

        expect($expandedGroups)->not->toContain('__true__');
    });

    it('can expand multiple groups', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__false__');

        $expandedGroups = $component->get('expandedGroups');

        expect($expandedGroups)->toContain('__true__')
            ->and($expandedGroups)->toContain('__false__');
    });
});

describe('Group Pagination', function (): void {
    it('can set page for a group', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('setGroupPage', '__true__', 2);

        $groupPages = $component->get('groupPages');

        expect($groupPages['__true__'])->toBe(2);
    });

    it('loads correct page data for group', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('groupPerPage', 5);
        $component->call('setGroupBy', 'is_published');

        // Get first page data
        $data1 = $component->get('data');
        $trueGroup1 = collect($data1['groups'])->firstWhere('key', '__true__');
        $firstPageIds = collect($trueGroup1['data'])->pluck('id')->toArray();

        // Go to second page
        $component->call('setGroupPage', '__true__', 2);

        $data2 = $component->get('data');
        $trueGroup2 = collect($data2['groups'])->firstWhere('key', '__true__');
        $secondPageIds = collect($trueGroup2['data'])->pluck('id')->toArray();

        // IDs should be different
        expect(array_intersect($firstPageIds, $secondPageIds))->toBeEmpty();
    });

    it('updates pagination info when changing page', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('groupPerPage', 5);
        $component->call('setGroupBy', 'is_published');
        // Expand the group first
        $component->call('toggleGroup', '__true__');
        // Then change page
        $component->call('setGroupPage', '__true__', 2);

        $data = $component->get('data');
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['pagination']['current_page'])->toBe(2);
    });

    it('independent pagination per group', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('groupPerPage', 5);
        $component->call('setGroupBy', 'is_published');
        // Expand both groups
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__false__');
        // Change page only for true group
        $component->call('setGroupPage', '__true__', 2);

        $data = $component->get('data');
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        expect($trueGroup['pagination']['current_page'])->toBe(2)
            ->and($falseGroup['pagination']['current_page'])->toBe(1);
    });
});

describe('Group Labels', function (): void {
    it('creates label for boolean true value', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['label'])->toContain('Yes');
    });

    it('creates label for boolean false value', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        expect($falseGroup['label'])->toContain('No');
    });
});

describe('Group Aggregates', function (): void {
    it('includes aggregates when aggregatableCols is set and group is expanded', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Use 'price' which is in enabledCols
        $component->set('aggregatableCols', ['sum' => ['price']]);
        $component->call('setGroupBy', 'is_published');
        // Expand groups to get aggregates
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__false__');

        $data = $component->get('data');
        $groups = $data['groups'];

        foreach ($groups as $group) {
            if ($group['expanded']) {
                expect($group)->toHaveKey('aggregates')
                    ->and($group['aggregates'])->toHaveKey('sum');
            }
        }
    });

    it('collapsed groups have aggregates in header', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->set('aggregatableCols', ['sum' => ['price']]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        // Aggregates should be calculated for all groups (shown in header)
        foreach ($data['groups'] as $group) {
            expect($group['aggregates'])->toHaveKey('sum');
        }
    });

    it('group aggregates are calculated per group', function (): void {
        // Delete existing posts and create with known prices
        Post::query()->delete();
        for ($i = 0; $i < 3; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 100]);
        }
        for ($i = 0; $i < 2; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'price' => 50]);
        }

        $component = Livewire::test(PostDataTable::class);

        $component->set('aggregatableCols', ['sum' => ['price']]);
        $component->call('setGroupBy', 'is_published');
        // Aggregates are now calculated for all groups (shown in header), no need to expand

        $data = $component->get('data');
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        // 3 posts * 100 price = 300
        expect((float) $trueGroup['aggregates']['sum']['price'])->toBe(300.0);
        // 2 posts * 50 price = 100
        expect((float) $falseGroup['aggregates']['sum']['price'])->toBe(100.0);
    });

    it('total aggregates still work with grouping', function (): void {
        Post::query()->delete();
        for ($i = 0; $i < 3; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 100]);
        }
        for ($i = 0; $i < 2; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'price' => 50]);
        }

        $component = Livewire::test(PostDataTable::class);

        $component->set('aggregatableCols', ['sum' => ['price']]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        // Total should be 300 + 100 = 400
        expect((float) $data['aggregates']['sum']['price'])->toBe(400.0);
    });
});

describe('Getters', function (): void {
    it('getGroupableCols returns array', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $groupableCols = $component->instance()->getGroupableCols();

        expect($groupableCols)->toBeArray();
    });

    it('getGroupableCols excludes relation columns', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $groupableCols = $component->instance()->getGroupableCols();

        expect($groupableCols)->toBeArray();

        foreach ($groupableCols as $col) {
            expect($col)->not->toContain('.');
        }
    });
});

describe('Ungrouped Mode', function (): void {
    it('returns standard pagination when not grouped', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Trigger data load
        $component->call('loadData');

        $data = $component->get('data');

        expect($data)->toHaveKey('data')
            ->and($data)->toHaveKey('current_page')
            ->and($data)->not->toHaveKey('groups');
    });

    it('switching from grouped to ungrouped returns standard data', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('setGroupBy', null);

        $data = $component->get('data');

        expect($data)->toHaveKey('data')
            ->and($data)->toHaveKey('current_page');
    });
});

describe('Grouping with Sorted Query', function (): void {
    it('works when query has existing ORDER BY clause', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Set a sort order first (this adds ORDER BY to the query)
        $component->set('orderBy', 'title');
        $component->set('orderAsc', true);

        // This should not throw SQL error about DISTINCT with ORDER BY
        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        expect($data)->toHaveKey('groups')
            ->and($data['groups'])->toBeArray()
            ->and($data['groups'])->toHaveCount(2);
    });

    it('works when query is sorted descending', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Set a descending sort order
        $component->set('orderBy', 'id');
        $component->set('orderAsc', false);

        // This should not throw SQL error
        $component->call('setGroupBy', 'is_published');

        $data = $component->get('data');

        expect($data)->toHaveKey('groups');
    });
});
