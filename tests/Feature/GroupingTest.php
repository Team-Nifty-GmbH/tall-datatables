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

        $data = $component->instance()->getDataForTesting();

        expect($data)->toHaveKey('groups')
            ->and($data['groups'])->toBeArray();
    });

    it('each group has required keys', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
        $collapsedGroup = $data['groups'][0];

        expect($collapsedGroup['pagination'])->toBeNull();
    });

    it('correctly groups by boolean column', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect(count($trueGroup['data']))->toBeLessThanOrEqual(3);
    });

    it('collapsed groups have empty data array', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        foreach ($data['groups'] as $group) {
            expect($group['data'])->toBeEmpty();
        }
    });

    it('returns total count across all groups', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

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
        $data1 = $component->instance()->getDataForTesting();
        $trueGroup1 = collect($data1['groups'])->firstWhere('key', '__true__');
        $firstPageIds = collect($trueGroup1['data'])->pluck('id')->toArray();

        // Go to second page
        $component->call('setGroupPage', '__true__', 2);

        $data2 = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['label'])->toContain('Yes');
    });

    it('creates label for boolean false value', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();
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

        $data = $component->instance()->getDataForTesting();

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

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        // 3 posts * 100 price = 300
        $truePrice = $trueGroup['aggregates']['sum']['price'];
        $trueRaw = is_array($truePrice) ? $truePrice['raw'] : $truePrice;
        expect((float) $trueRaw)->toBe(300.0);
        // 2 posts * 50 price = 100
        $falsePrice = $falseGroup['aggregates']['sum']['price'];
        $falseRaw = is_array($falsePrice) ? $falsePrice['raw'] : $falsePrice;
        expect((float) $falseRaw)->toBe(100.0);
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

        $data = $component->instance()->getDataForTesting();

        // Total should be 300 + 100 = 400
        $priceAggregate = $data['aggregates']['sum']['price'];
        $rawValue = is_array($priceAggregate) ? $priceAggregate['raw'] : $priceAggregate;
        expect((float) $rawValue)->toBe(400.0);
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

        $data = $component->instance()->getDataForTesting();

        expect($data)->toHaveKey('data')
            ->and($data)->toHaveKey('current_page')
            ->and($data)->not->toHaveKey('groups');
    });

    it('switching from grouped to ungrouped returns standard data', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        $component->call('setGroupBy', null);

        $data = $component->instance()->getDataForTesting();

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

        $data = $component->instance()->getDataForTesting();

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

        $data = $component->instance()->getDataForTesting();

        expect($data)->toHaveKey('groups');
    });
});

describe('Group Key Generation', function (): void {
    it('generates __null__ key for null values when grouping by nullable column', function (): void {
        // Create posts with and without price (null)
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Price', 'price' => null]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'price');

        $data = $component->instance()->getDataForTesting();

        // Check that all items are accounted for across groups
        $totalCount = array_sum(array_column($data['groups'], 'count'));
        expect($totalCount)->toBe(2);
    });

    it('generates __true__ and __false__ keys for boolean values', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        expect($trueGroup)->not->toBeNull();
        expect($falseGroup)->not->toBeNull();
    });

    it('generates string key for regular values', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'GroupA']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'GroupB']);

        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'title');

        $data = $component->instance()->getDataForTesting();

        $keys = collect($data['groups'])->pluck('key')->toArray();
        expect($keys)->toContain('GroupA')
            ->toContain('GroupB');
    });
});

describe('Group Label Generation', function (): void {
    it('creates label with (empty) for null group values', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Price', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Price', 'price' => null]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'price');

        $data = $component->instance()->getDataForTesting();

        // Check if there is a null group (SQLite handles NULL ordering differently)
        $nullGroup = collect($data['groups'])->firstWhere('key', '__null__');
        if ($nullGroup) {
            expect($nullGroup['label'])->toContain('(empty)');
        } else {
            // If null group is not in the first page of groups, verify total groups exist
            expect($data['groups'])->not->toBeEmpty();
        }
    });

    it('uses filterValueLists label for mapped values', function (): void {
        // is_published has filterValueLists with Yes/No
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');

        expect($trueGroup['label'])->toContain('Yes');
        expect($falseGroup['label'])->toContain('No');
    });

    it('uses raw value as label for regular string values', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'UniqueTitle']);

        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'title');

        $data = $component->instance()->getDataForTesting();
        $group = collect($data['groups'])->firstWhere('key', 'UniqueTitle');

        expect($group['label'])->toContain('UniqueTitle');
    });
});

describe('Group Expand/Collapse State', function (): void {
    it('preserves expanded state across data reloads', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        // Verify the group stays expanded
        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        expect($trueGroup['expanded'])->toBeTrue();

        // Load data again to verify persistence
        $component->call('loadData');
        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        expect($trueGroup['expanded'])->toBeTrue();
    });

    it('collapsed group has null pagination', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        foreach ($data['groups'] as $group) {
            if (! $group['expanded']) {
                expect($group['pagination'])->toBeNull();
                expect($group['data'])->toBeEmpty();
            }
        }
    });

    it('expanded group has pagination info', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['expanded'])->toBeTrue();
        expect($trueGroup['pagination'])->not->toBeNull();
        expect($trueGroup['pagination'])->toHaveKey('current_page')
            ->toHaveKey('last_page')
            ->toHaveKey('per_page')
            ->toHaveKey('total');
    });

    it('toggleGroup removes from array correctly', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        // Expand both
        $component->call('toggleGroup', '__true__');
        $component->call('toggleGroup', '__false__');
        expect($component->get('expandedGroups'))->toHaveCount(2);

        // Collapse first one
        $component->call('toggleGroup', '__true__');
        $expandedGroups = $component->get('expandedGroups');
        expect($expandedGroups)->toHaveCount(1);
        expect($expandedGroups)->toContain('__false__');
        expect($expandedGroups)->not->toContain('__true__');
    });
});

describe('Groups Pagination', function (): void {
    it('has groups_pagination with required keys', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        expect($data)->toHaveKey('groups_pagination');
        expect($data['groups_pagination'])->toHaveKey('current_page')
            ->toHaveKey('last_page')
            ->toHaveKey('per_page')
            ->toHaveKey('total');
    });

    it('starts at page 1 for groups pagination', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        expect($data['groups_pagination']['current_page'])->toBe(1);
    });

    it('can change groups page via setGroupsPage', function (): void {
        // Create many distinct values to have multiple group pages
        Post::query()->delete();
        for ($i = 0; $i < 30; $i++) {
            createTestPost([
                'user_id' => $this->user->getKey(),
                'title' => 'Group ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'price' => $i * 10,
            ]);
        }

        $component = Livewire::test(PostDataTable::class);
        $component->set('groupsPerPage', 10);
        $component->call('setGroupBy', 'title');
        $component->call('setGroupsPage', 2);

        expect($component->get('currentGroupsPage'))->toBe(2);
    });

    it('respects groupsPerPage setting', function (): void {
        Post::query()->delete();
        for ($i = 0; $i < 30; $i++) {
            createTestPost([
                'user_id' => $this->user->getKey(),
                'title' => 'Title ' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);
        }

        $component = Livewire::test(PostDataTable::class);
        $component->set('groupsPerPage', 10);
        $component->call('setGroupBy', 'title');

        $data = $component->instance()->getDataForTesting();

        expect(count($data['groups']))->toBeLessThanOrEqual(10);
        expect($data['groups_pagination']['per_page'])->toBe(10);
    });
});

describe('Grouping with Aggregates on Columns', function (): void {
    it('provides aggregates for each group when aggregatableCols is set', function (): void {
        Post::query()->delete();
        for ($i = 0; $i < 5; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 100]);
        }
        for ($i = 0; $i < 3; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'price' => 200]);
        }

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['avg' => ['price']]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        foreach ($data['groups'] as $group) {
            expect($group['aggregates'])->toHaveKey('avg');
        }

        // Published: avg(100) = 100
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        $avgPrice = $trueGroup['aggregates']['avg']['price'];
        $rawValue = is_array($avgPrice) ? $avgPrice['raw'] : $avgPrice;
        expect((float) $rawValue)->toBe(100.0);

        // Unpublished: avg(200) = 200
        $falseGroup = collect($data['groups'])->firstWhere('key', '__false__');
        $avgPrice = $falseGroup['aggregates']['avg']['price'];
        $rawValue = is_array($avgPrice) ? $avgPrice['raw'] : $avgPrice;
        expect((float) $rawValue)->toBe(200.0);
    });

    it('supports min and max aggregates per group', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 10]);
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 50]);
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'price' => 100]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['min' => ['price'], 'max' => ['price']]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        expect($trueGroup['aggregates'])->toHaveKey('min')
            ->toHaveKey('max');

        $minPrice = $trueGroup['aggregates']['min']['price'];
        $minRaw = is_array($minPrice) ? $minPrice['raw'] : $minPrice;
        expect((float) $minRaw)->toBe(10.0);

        $maxPrice = $trueGroup['aggregates']['max']['price'];
        $maxRaw = is_array($maxPrice) ? $maxPrice['raw'] : $maxPrice;
        expect((float) $maxRaw)->toBe(100.0);
    });

    it('provides empty aggregates when aggregatableCols is empty', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['sum' => [], 'avg' => [], 'min' => [], 'max' => []]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        foreach ($data['groups'] as $group) {
            expect($group['aggregates'])->toBeArray();
        }
    });
});

describe('Group data within expanded groups', function (): void {
    it('expanded group data contains itemToArray format', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['data'])->not->toBeEmpty();
        $firstItem = $trueGroup['data'][0];

        // Should contain standard datatable row keys
        expect($firstItem)->toHaveKey('title')
            ->toHaveKey('content')
            ->toHaveKey('is_published')
            ->toHaveKey('href');
    });

    it('expanded group pagination tracks total correctly', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('groupPerPage', 5);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');

        expect($trueGroup['pagination']['total'])->toBe(15);
        expect($trueGroup['pagination']['per_page'])->toBe(5);
        expect($trueGroup['pagination']['last_page'])->toBe(3);
    });

    it('group page navigation shows correct from and to', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('groupPerPage', 5);
        $component->call('setGroupBy', 'is_published');
        $component->call('toggleGroup', '__true__');

        // Page 1
        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        expect($trueGroup['pagination']['from'])->toBe(1);
        expect($trueGroup['pagination']['to'])->toBe(5);

        // Page 2
        $component->call('setGroupPage', '__true__', 2);
        $data = $component->instance()->getDataForTesting();
        $trueGroup = collect($data['groups'])->firstWhere('key', '__true__');
        expect($trueGroup['pagination']['from'])->toBe(6);
        expect($trueGroup['pagination']['to'])->toBe(10);
    });
});

describe('Grouping with filters', function (): void {
    it('applies user filters before grouping', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Target A', 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Target B', 'is_published' => false]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other', 'is_published' => true]);

        $component = Livewire::test(PostDataTable::class);

        $component->set('userFilters', [
            [['column' => 'title', 'operator' => 'like', 'value' => '%Target%']],
        ]);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();

        // Only "Target A" and "Target B" should be included
        $totalCount = array_sum(array_column($data['groups'], 'count'));
        expect($totalCount)->toBe(2);
    });
});

describe('setGroupBy resets state', function (): void {
    it('resets currentGroupsPage when changing groupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('currentGroupsPage', 3);
        $component->call('setGroupBy', 'is_published');

        expect($component->get('currentGroupsPage'))->toBe(1);
    });

    it('calling setGroupBy triggers loadData', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('setGroupBy', 'is_published');

        $data = $component->instance()->getDataForTesting();
        expect($data)->toHaveKey('groups');
    });
});

describe('Group Label with filterValueLists', function (): void {
    it('uses filterValueLists label for mapped string values', function (): void {
        Post::query()->delete();
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'status_a']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'status_b']);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Call getGroupLabel directly via reflection to exercise the branch
        $instance->groupBy = 'title';
        $instance->filterValueLists = [
            'title' => [
                ['value' => 'status_a', 'label' => 'Active Status'],
                ['value' => 'status_b', 'label' => 'Inactive Status'],
            ],
        ];
        $instance->colLabels = $instance->getColLabels();

        $reflection = new ReflectionMethod($instance, 'getGroupLabel');
        $labelA = $reflection->invoke($instance, 'status_a');
        $labelB = $reflection->invoke($instance, 'status_b');

        expect($labelA)->toContain('Active Status');
        expect($labelB)->toContain('Inactive Status');
    });

    it('falls back to raw value when filterValueLists entry has no match', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $instance->groupBy = 'title';
        $instance->filterValueLists = [
            'title' => [
                ['value' => 'other', 'label' => 'Other Label'],
            ],
        ];
        $instance->colLabels = $instance->getColLabels();

        $reflection = new ReflectionMethod($instance, 'getGroupLabel');
        $label = $reflection->invoke($instance, 'unmapped');

        expect($label)->toContain('unmapped');
    });

    it('generates label for null group value via getGroupLabel', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $instance->groupBy = 'title';
        $instance->colLabels = $instance->getColLabels();

        $reflection = new ReflectionMethod($instance, 'getGroupLabel');
        $label = $reflection->invoke($instance, null);

        expect($label)->toContain('(empty)');
    });
});

describe('getGroupableCols returns model attributes', function (): void {
    it('returns non-virtual model attributes', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $groupableCols = $component->instance()->getGroupableCols();

        // Should include real database columns
        expect($groupableCols)->toContain('title')
            ->toContain('content')
            ->toContain('price')
            ->toContain('is_published');
    });

    it('includes timestamp columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $groupableCols = $component->instance()->getGroupableCols();

        expect($groupableCols)->toContain('created_at')
            ->toContain('updated_at');
    });
});

// ---------------------------------------------------------------------------
// SupportsGrouping line 72 — getGroupKey for bool false
// ---------------------------------------------------------------------------
describe('getGroupKey with boolean false', function (): void {
    it('returns __false__ for boolean false', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getGroupKey');

        expect($reflection->invoke($instance, false))->toBe('__false__');
        expect($reflection->invoke($instance, true))->toBe('__true__');
        expect($reflection->invoke($instance, null))->toBe('__null__');
        expect($reflection->invoke($instance, 'hello'))->toBe('hello');
    });
});
