<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('getConfig', function (): void {
    it('includes groupable key', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $config = $component->instance()->getConfig();

        expect($config)->toHaveKey('groupable');
        expect($config['groupable'])->toBeArray();
    });

    it('includes groupLabels key', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $config = $component->instance()->getConfig();

        expect($config)->toHaveKey('groupLabels');
        expect($config['groupLabels'])->toBeArray();
    });

    it('enabledCols matches component enabled columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $config = $component->instance()->getConfig();

        expect($config['enabledCols'])->toContain('title');
        expect($config['enabledCols'])->toContain('content');
    });

    it('selectable matches component isSelectable', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $config = $component->instance()->getConfig();

        expect($config['selectable'])->toBeTrue();
    });
});

describe('getViewData', function (): void {
    it('includes all expected keys', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($viewData)
            ->toHaveKey('searchable')
            ->toHaveKey('tableHeadColAttributes')
            ->toHaveKey('selectAttributes')
            ->toHaveKey('includeBefore')
            ->toHaveKey('includeAfter')
            ->toHaveKey('selectValue')
            ->toHaveKey('showRestoreButton')
            ->toHaveKey('aggregatable')
            ->toHaveKey('isExportable');
    });

    it('caches view data on second call', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $first = $reflection->invoke($instance);
        $second = $reflection->invoke($instance);

        expect($first)->toBe($second);
    });

    it('includeBefore defaults to null', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($viewData['includeBefore'])->toBeNull();
    });

    it('includeAfter defaults to null', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($viewData['includeAfter'])->toBeNull();
    });
});

describe('getIslandData', function (): void {
    it('returns the same data as getViewData', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $viewDataReflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $viewDataReflection->invoke($instance);
        $islandData = $instance->getIslandData();

        expect($islandData)->toBe($viewData);
    });
});

describe('getColLabels', function (): void {
    it('returns labels for all enabled columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getColLabels();

        expect($labels)
            ->toHaveKey('title')
            ->toHaveKey('content')
            ->toHaveKey('is_published')
            ->toHaveKey('created_at');
    });

    it('converts snake_case to headline format', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getColLabels();

        expect($labels['is_published'])->toBe('Is Published');
        expect($labels['created_at'])->toBe('Created At');
    });

    it('accepts custom columns array', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getColLabels(['title', 'content']);

        expect($labels)->toHaveCount(2);
        expect($labels)->toHaveKey('title');
        expect($labels)->toHaveKey('content');
    });

    it('generates relation labels with arrow notation', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class);
        $labels = $component->instance()->getColLabels();

        expect($labels)->toHaveKey('user.name');
        expect($labels['user.name'])->toContain('->');
    });
});

describe('getOperatorLabels', function (): void {
    it('includes time unit labels', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getOperatorLabels();

        expect($labels)
            ->toHaveKey('minutes')
            ->toHaveKey('hours')
            ->toHaveKey('days')
            ->toHaveKey('weeks')
            ->toHaveKey('months')
            ->toHaveKey('years');
    });

    it('includes singular time unit labels', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getOperatorLabels();

        expect($labels)
            ->toHaveKey('minute')
            ->toHaveKey('hour')
            ->toHaveKey('day')
            ->toHaveKey('week')
            ->toHaveKey('month')
            ->toHaveKey('year');
    });

    it('includes Start of and End of labels', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getOperatorLabels();

        expect($labels)
            ->toHaveKey('Start of')
            ->toHaveKey('End of')
            ->toHaveKey('Now');
    });
});

describe('getGroupLabels', function (): void {
    it('includes all required label keys', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getGroupLabels();

        expect($labels)
            ->toHaveKey('entries')
            ->toHaveKey('showing')
            ->toHaveKey('to')
            ->toHaveKey('of')
            ->toHaveKey('groups')
            ->toHaveKey('noGrouping')
            ->toHaveKey('empty')
            ->toHaveKey('sum')
            ->toHaveKey('avg')
            ->toHaveKey('min')
            ->toHaveKey('max');
    });

    it('entries label contains pipe separator for singular and plural', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $labels = $component->instance()->getGroupLabels();

        expect($labels['entries'])->toContain('|');
    });
});

describe('clearFiltersAndSort', function (): void {
    it('resets userOrderBy to empty string', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title')
            ->call('clearFiltersAndSort');

        expect($component->get('userOrderBy'))->toBe('');
    });

    it('resets userOrderAsc to true', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title')
            ->call('sortTable', 'title')
            ->call('clearFiltersAndSort');

        expect($component->get('userOrderAsc'))->toBeTrue();
    });

    it('resets search to empty string', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('search', 'some query')
            ->call('clearFiltersAndSort');

        expect($component->get('search'))->toBe('');
    });

    it('resets groupBy to null', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('groupBy', 'is_published')
            ->call('clearFiltersAndSort');

        expect($component->get('groupBy'))->toBeNull();
    });

    it('resets loadedFilterId to null', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', 42)
            ->call('clearFiltersAndSort');

        expect($component->get('loadedFilterId'))->toBeNull();
    });

    it('resets textFilters to empty array', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'test')
            ->call('clearFiltersAndSort');

        expect($component->get('textFilters'))->toBe([]);
    });
});

describe('sortTable', function (): void {
    it('sets userOrderBy to the given column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title');

        expect($component->get('userOrderBy'))->toBe('title');
    });

    it('toggles direction when sorting same column twice', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title');

        expect($component->get('userOrderAsc'))->toBeTrue();

        $component->call('sortTable', 'title');

        expect($component->get('userOrderAsc'))->toBeFalse();
    });

    it('keeps current direction when changing to different column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title')
            ->call('sortTable', 'title')
            ->call('sortTable', 'content');

        expect($component->get('userOrderBy'))->toBe('content');
        expect($component->get('userOrderAsc'))->toBeFalse();
    });
});

describe('startSearch', function (): void {
    it('resets selected to empty array', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('selected', [1, 2, 3])
            ->call('startSearch');

        expect($component->get('selected'))->toBe([]);
    });

    it('resets page to 1', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('page', 5)
            ->call('startSearch');

        expect($component->get('page'))->toBe(1);
    });
});

describe('dehydrate', function (): void {
    it('clears data after render cycle', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $instance = $component->instance();

        // Data should be loaded initially
        expect($instance->getDataForTesting())->not->toBeEmpty();

        // After dehydrate, data should be cleared
        $instance->dehydrate();
        expect($instance->data)->toBe([]);
    });
});

describe('forceRender', function (): void {
    it('is a no-op for backwards compatibility', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('forceRender');

        expect($component->get('initialized'))->toBeTrue();
    });
});

describe('forgetSessionFilter', function (): void {
    it('clears sessionFilter array', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('sessionFilter', [['column' => 'title', 'operator' => '=', 'value' => 'test']])
            ->call('forgetSessionFilter');

        expect($component->get('sessionFilter'))->toBe([]);
    });

    it('reloads data when loadData flag is true', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('forgetSessionFilter', true);

        expect($component->get('initialized'))->toBeTrue();
    });
});

describe('formatFilterBadgeValue', function (): void {
    it('returns original value when no formatter exists', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $result = $instance->formatFilterBadgeValue('title', 'hello');

        expect($result)->toBe('hello');
    });

    it('returns original value on formatter error', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $result = $instance->formatFilterBadgeValue('nonexistent_column', 'some value');

        expect($result)->toBe('some value');
    });
});

describe('getParsedTextFilters', function (): void {
    it('returns empty collection when no text filters', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $result = $component->instance()->getParsedTextFilters();

        expect($result)->toBeEmpty();
    });

    it('returns parsed text filters from userFilters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        $result = $component->instance()->getParsedTextFilters();

        expect($result)->toHaveCount(1);
        expect($result->first()['column'])->toBe('title');
    });

    it('strips LIKE wildcards from display value', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        $result = $component->instance()->getParsedTextFilters();

        expect($result->first()['value'])->toBe('Alpha');
    });
});

describe('removeFilter', function (): void {
    it('removes a specific filter from a group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    ['column' => 'title', 'operator' => '=', 'value' => 'A'],
                    ['column' => 'content', 'operator' => '=', 'value' => 'B'],
                ],
            ])
            ->call('removeFilter', 0, 0);

        $userFilters = $component->get('userFilters');

        expect($userFilters)->toHaveCount(1);
        expect($userFilters[0][0]['column'])->toBe('content');
    });

    it('removes empty group after last filter removed', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    ['column' => 'title', 'operator' => '=', 'value' => 'A'],
                ],
            ])
            ->call('removeFilter', 0, 0);

        $userFilters = $component->get('userFilters');

        expect($userFilters)->toBeEmpty();
    });

    it('does nothing for non-existent filter index', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    ['column' => 'title', 'operator' => '=', 'value' => 'A'],
                ],
            ])
            ->call('removeFilter', 0, 99);

        $userFilters = $component->get('userFilters');

        expect($userFilters)->toHaveCount(1);
    });

    it('also removes text-source filter from textFilters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        expect($component->get('textFilters'))->not->toBeEmpty();

        $component->call('removeFilter', 0, 0);

        expect($component->get('textFilters'))->toBeEmpty();
    });
});

describe('removeFilterGroup', function (): void {
    it('removes an entire filter group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'A']],
                [['column' => 'content', 'operator' => '=', 'value' => 'B']],
            ])
            ->call('removeFilterGroup', 0);

        $userFilters = $component->get('userFilters');

        expect($userFilters)->toHaveCount(1);
        expect($userFilters[0][0]['column'])->toBe('content');
    });

    it('does nothing for non-existent group index', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'A']],
            ])
            ->call('removeFilterGroup', 99);

        $userFilters = $component->get('userFilters');

        expect($userFilters)->toHaveCount(1);
    });

    it('cleans up textFilters for text-source filters in the group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1);

        expect($component->get('textFilters'))->toHaveCount(2);

        $component->call('removeFilterGroup', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(1);
    });
});

describe('applyUserFilters', function (): void {
    it('removes orphaned textFilters not in userFilters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha')
            ->set('userFilters', []);

        $component->call('applyUserFilters');

        expect($component->get('textFilters'))->toBeEmpty();
    });

    it('resets loadedFilterId', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', 5)
            ->call('applyUserFilters');

        expect($component->get('loadedFilterId'))->toBeNull();
    });

    it('refreshes colLabels', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $initialLabels = $component->get('colLabels');

        $component->call('applyUserFilters');

        expect($component->get('colLabels'))->toBeArray();
    });
});

describe('placeholder', function (): void {
    it('returns a view instance', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $result = $instance->placeholder();

        expect($result)->toBeInstanceOf(Illuminate\Contracts\View\View::class);
    });
});

describe('reloadData', function (): void {
    it('reloads data when called', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('reloadData');

        expect($component->get('initialized'))->toBeTrue();
    });
});

describe('setPerPage', function (): void {
    it('adjusts page number when current page would be beyond last page', function (): void {
        for ($i = 0; $i < 30; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 3);

        expect($component->get('page'))->toBe(3);

        $component->call('setPerPage', 15);

        // 30 / 15 = 2 pages, page 3 would be beyond, so it adjusts
        expect($component->get('perPage'))->toBe(15);
    });
});

describe('loadMore', function (): void {
    it('doubles perPage', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadMore');

        expect($component->get('perPage'))->toBe(20);
    });

    it('doubles perPage multiple times', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadMore')
            ->call('loadMore');

        expect($component->get('perPage'))->toBe(40);
    });
});

describe('updatedSearch', function (): void {
    it('triggers startSearch which resets page and selected', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('selected', [1])
            ->set('page', 3)
            ->set('search', 'something');

        expect($component->get('selected'))->toBe([]);
        expect($component->get('page'))->toBe(1);
    });
});

describe('compileActions', function (): void {
    it('returns empty table actions by default', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');
        $actions = $reflection->invoke($instance, 'table');

        expect($actions)->toBeArray();
    });

    it('returns empty row actions by default', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');
        $actions = $reflection->invoke($instance, 'row');

        expect($actions)->toBeArray();
    });

    it('caches action results', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');
        $first = $reflection->invoke($instance, 'table');
        $second = $reflection->invoke($instance, 'table');

        expect($first)->toBe($second);
    });
});

describe('getSearchRoute', function (): void {
    it('returns empty string when no search route configured', function (): void {
        config(['tall-datatables.search_route' => null]);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getSearchRoute');
        $result = $reflection->invoke($instance);

        expect($result)->toBe('');
    });
});

describe('showRestoreButton', function (): void {
    it('returns false when restore method does not exist', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'showRestoreButton');
        $result = $reflection->invoke($instance);

        expect($result)->toBeFalse();
    });
});

describe('getAppends', function (): void {
    it('returns the appends array', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getAppends');
        $result = $reflection->invoke($instance);

        expect($result)->toBeArray();
    });
});

describe('getIncludedRelations', function (): void {
    it('returns self relation for non-dot columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getIncludedRelations');
        $result = $reflection->invoke($instance);

        expect($result)->toHaveKey('self');
        expect($result['self']['model'])->toBe(Post::class);
    });

    it('returns relation info for dot-notation columns', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getIncludedRelations');
        $result = $reflection->invoke($instance);

        expect($result)->toHaveKey('user');
        expect($result['user']['loaded_columns'])->toHaveKey('user.name');
        expect($result['user']['loaded_columns'])->toHaveKey('user.email');
    });
});
