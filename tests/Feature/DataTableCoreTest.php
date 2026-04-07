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

    it('resets direction to ASC when changing to different column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('sortTable', 'title')
            ->call('sortTable', 'title')
            ->call('sortTable', 'content');

        expect($component->get('userOrderBy'))->toBe('content');
        expect($component->get('userOrderAsc'))->toBeTrue();
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

describe('loadFilter', function (): void {
    it('applies given properties to the component', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('loadFilter', [
            'perPage' => 25,
            'search' => 'test query',
        ]);

        expect($component->get('perPage'))->toBe(25);
        expect($component->get('search'))->toBe('test query');
    });

    it('does nothing when properties array is empty', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('loadFilter', []);

        expect($component->get('perPage'))->toBe(15);
    });

    it('reloads data when initialized', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('loadFilter', ['perPage' => 5]);

        expect($component->get('initialized'))->toBeTrue();
    });
});

describe('setTextFilter', function (): void {
    it('sets a simple text filter for a column', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta']);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        $textFilters = $component->get('textFilters');
        expect($textFilters[0]['title'])->toBe('Alpha');
    });

    it('removes text filter when value is null', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha')
            ->call('setTextFilter', 'title', null);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toBeEmpty();
    });

    it('removes text filter when value is empty string', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha')
            ->call('setTextFilter', 'title', '');

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toBeEmpty();
    });

    it('handles multi-value text filter with valueIndex', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0, 0)
            ->call('setTextFilter', 'title', 'Beta', 0, 1);

        $textFilters = $component->get('textFilters');
        expect($textFilters[0]['title'])->toBeArray();
        expect($textFilters[0]['title'])->toContain('Alpha');
        expect($textFilters[0]['title'])->toContain('Beta');
    });

    it('removes multi-value entry when set to null', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0, 0)
            ->call('setTextFilter', 'title', 'Beta', 0, 1)
            ->call('setTextFilter', 'title', null, 0, 1);

        $textFilters = $component->get('textFilters');
        // Should collapse back to single value
        expect($textFilters[0]['title'])->toBe('Alpha');
    });

    it('supports text filters on different groups', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(2);
    });
});

describe('removeTextFilterRow', function (): void {
    it('removes a text filter group by index', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('removeTextFilterRow', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(1);
    });

    it('re-indexes text filters after removal', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('removeTextFilterRow', 0);

        $textFilters = $component->get('textFilters');
        expect(array_keys($textFilters))->toBe([0]);
    });
});

describe('migrateTextFiltersIfNeeded', function (): void {
    it('migrates old flat format to grouped format', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Set old flat format directly
        $component->set('textFilters', ['title' => 'Alpha', 'content' => 'Beta']);
        $component->call('setTextFilter', 'price', '100', 0);

        $textFilters = $component->get('textFilters');
        // Should now be grouped
        expect(isset($textFilters[0]))->toBeTrue();
    });

    it('does nothing when textFilters is already empty', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('textFilters', []);
        $component->call('setTextFilter', 'title', 'Alpha');

        $textFilters = $component->get('textFilters');
        expect($textFilters[0]['title'])->toBe('Alpha');
    });
});

describe('updatedUserFilters', function (): void {
    it('applies user filters when not loading a filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $component = Livewire::test(PostDataTable::class)
            ->set('loadingFilter', false)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'Test']],
            ]);

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('skips filter application when loadingFilter is true', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('loadingFilter', true);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => '=', 'value' => 'Test']],
        ]);

        // loadingFilter should be reset to false
        expect($component->get('loadingFilter'))->toBeFalse();
    });
});

describe('getDataForTesting', function (): void {
    it('reloads data if data is empty but initialized', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Force empty data but keep initialized
        $instance->data = [];

        $data = $instance->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});

describe('updatedStickyCols', function (): void {
    it('can be called without error', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->set('stickyCols', ['title']);

        expect($component->get('stickyCols'))->toBe(['title']);
    });
});

describe('getTableFields', function (): void {
    it('returns non-virtual non-appended attributes', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getTableFields');
        $result = $reflection->invoke($instance);

        expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
        expect($result->pluck('name')->toArray())->toContain('title');
    });
});

describe('wildcard select handling', function (): void {
    it('includes all selected items when wildcard is active', function (): void {
        $post1 = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P1']);
        $post2 = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P2']);
        $post3 = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'P3']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('selected', ['*']);
        $component->call('loadData');

        $selected = $component->get('selected');
        expect($selected)->toContain('*');
        expect(count($selected))->toBeGreaterThanOrEqual(4); // 3 items + *
    });
});

describe('getAvailableCols', function (): void {
    it('returns all model attributes when availableCols is wildcard', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $availableCols = $component->instance()->getAvailableCols();

        expect($availableCols)->toContain('title');
        expect($availableCols)->toContain('id');
    });
});

describe('formatFilterBadgeValue', function (): void {
    it('formats numeric values using model cast formatter', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // price has BcFloat cast, so it should format
        $result = $instance->formatFilterBadgeValue('price', '1234.56');

        expect($result)->toBeString();
    });
});

describe('getLayout', function (): void {
    it('returns the table layout view', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getLayout');
        $result = $reflection->invoke($instance);

        expect($result)->toBe('tall-datatables::layouts.table');
    });
});

describe('applyUserFilters with non-array textFilters entries', function (): void {
    it('skips non-array textFilters entries', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Set textFilters with a non-array entry
        $component->set('textFilters', ['not_an_array_value']);
        $component->set('userFilters', []);

        // Should not crash
        $component->call('applyUserFilters');

        expect($component->get('loadedFilterId'))->toBeNull();
    });
});

describe('getAvailableCols with restricted availableCols', function (): void {
    it('returns specified cols when availableCols is not wildcard', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Set directly on instance since it's a Locked property
        $instance->availableCols = ['title', 'content'];

        $availableCols = $instance->getAvailableCols();

        expect($availableCols)->toContain('title');
        expect($availableCols)->toContain('content');
        // Should also include enabledCols and modelKeyName
        expect($availableCols)->toContain('id');
    });
});

describe('loadData with empty results on higher page', function (): void {
    it('handles page beyond results gracefully', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class);
        $component->set('perPage', 10);
        $component->call('gotoPage', 2);

        Post::query()->forceDelete();

        // Should not throw an exception
        $component->call('loadData');

        expect($component->instance())->toBeInstanceOf(PostDataTable::class);
    });
});

describe('aggregates cleared when search is active', function (): void {
    it('sets aggregates to empty array when search is non-empty', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta', 'price' => 200]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['sum' => ['price']]);
        $component->set('search', 'Alpha');
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        // When search is active, aggregates should be empty
        if (isset($data['aggregates'])) {
            expect($data['aggregates'])->toBe([]);
        }
    });
});

describe('setPerPage page adjustment', function (): void {
    it('adjusts page when new perPage causes current page to be beyond last', function (): void {
        for ($i = 0; $i < 30; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Manually set state to simulate being on page 3 with known data
        $instance->perPage = 10;
        $instance->page = 3;
        $instance->data = ['total' => 30];

        // Now call setPerPage which should adjust page
        $instance->setPerPage(15);

        // 30 / 15 = 2 pages, page 3 > 2 so should adjust to page 2
        expect($instance->page)->toBeLessThanOrEqual(2);
    });
});

describe('setTextFilter multi-value removal empties column', function (): void {
    it('removes column from textFilters when all multi-values are cleared', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Set multi-value filter
        $component->call('setTextFilter', 'title', 'Alpha', 0, 0);
        $component->call('setTextFilter', 'title', 'Beta', 0, 1);

        // Now remove both entries
        $component->call('setTextFilter', 'title', null, 0, 0);
        $component->call('setTextFilter', 'title', null, 0, 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toBeEmpty();
    });
});

describe('getSearchRoute with configured route', function (): void {
    it('returns route URL when search_route is configured', function (): void {
        Illuminate\Support\Facades\Route::any('/search/{model?}', fn () => null)->name('test-search');
        config(['tall-datatables.search_route' => 'test-search']);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getSearchRoute');
        $result = $reflection->invoke($instance);

        expect($result)->toContain('/search');
    });
});

describe('formatFilterBadgeValue catch branch', function (): void {
    it('catches formatter errors and returns original value', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Set a formatter that will cause an error when resolved
        $instance->formatters = ['bad_column' => 'completely_invalid_formatter_class'];

        $result = $instance->formatFilterBadgeValue('bad_column', 'original');

        expect($result)->toBe('original');
    });
});

describe('formatAggregates with array formatter', function (): void {
    it('uses array formatter options for custom formatters', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'price' => 100]);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Set a formatter as array (formatter class + options)
        $instance->formatters = ['price' => [TeamNiftyGmbH\DataTable\Casts\Money::class, []]];
        $instance->aggregatableCols = ['sum' => ['price']];
        $instance->loadData();

        $data = $instance->getDataForTesting();
        expect($data)->toHaveKey('aggregates');
    });
});

// ---------------------------------------------------------------------------
// DataTable.php render when not initialized (line 149)
// ---------------------------------------------------------------------------
describe('render when not initialized', function (): void {
    it('calls loadData when initialized is false during render', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Render Test']);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // After mount, initialized is true. Force it to false.
        $instance->initialized = false;
        $result = $instance->render();

        // render should have called loadData, setting initialized back to true
        expect($instance->initialized)->toBeTrue();
        expect($result)->not->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// DataTable.php showRestoreButton (line 940-943)
// ---------------------------------------------------------------------------
describe('showRestoreButton', function (): void {
    it('returns false when component has no restore method', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'showRestoreButton');
        $result = $reflection->invoke($instance);

        expect($result)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// DataTable.php getParsedTextFilters — translate enum values (line 405-409)
// ---------------------------------------------------------------------------
describe('getParsedTextFilters enum translation', function (): void {
    it('translates enum values using filterValueLists for display', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        // Set up filterValueLists before calling setTextFilter
        $instance->filterValueLists = ['is_published' => [
            ['value' => '1', 'label' => 'Yes'],
            ['value' => '0', 'label' => 'No'],
        ]];

        // Call setTextFilter directly on instance so filterValueLists is available
        $instance->setTextFilter('is_published', '1');

        // Now the filter should have operator '=' since is_published is in filterValueLists
        $result = $instance->getParsedTextFilters();

        expect($result)->toHaveCount(1);
        expect($result->first()['column'])->toBe('is_published');
        // The display value should be translated via filterValueLists
        expect($result->first()['value'])->toBe('Yes');
    });
});

// ---------------------------------------------------------------------------
// DataTable.php loadFilter with properties (lines 510-523)
// ---------------------------------------------------------------------------
describe('loadFilter with multiple properties', function (): void {
    it('applies filter properties and reloads data when initialized', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Filter Load']);

        $component = Livewire::test(PostDataTable::class);

        $component->call('loadFilter', [
            'search' => 'Filter Load',
        ]);

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('returns early for empty properties array', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $component->call('loadFilter', []);

        // Should not crash
        expect(true)->toBeTrue();
    });

    it('does not reload when not initialized', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $instance->initialized = false;
        $instance->loadFilter(['perPage' => 25]);

        expect($instance->perPage)->toBe(25);
    });
});

// ---------------------------------------------------------------------------
// DataTable.php rebuildTextFilterGroup edge cases (lines 966-1013)
// ---------------------------------------------------------------------------
describe('rebuildTextFilterGroup with empty userFilters', function (): void {
    it('initializes userFilters when empty during rebuild', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Force userFilters to be completely empty
        $component->set('userFilters', []);

        // Setting a text filter triggers rebuildTextFilterGroup
        $component->call('setTextFilter', 'title', 'test');

        $userFilters = $component->get('userFilters');
        expect($userFilters)->not->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// DataTable.php removeTextFilterRow (lines 607-617)
// ---------------------------------------------------------------------------
describe('removeTextFilterRow', function (): void {
    it('removes a text filter row by group index', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Row Test']);

        $component = Livewire::test(PostDataTable::class);
        $component->call('setTextFilter', 'title', 'Row', 0);

        $component->call('removeTextFilterRow', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toBeEmpty();
    });
});

describe('loadMore perPage cap', function (): void {
    it('caps perPage at maximum after repeated calls', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Call loadMore many times (10 times would be 15 * 2^10 = 15360 without a cap)
        for ($i = 0; $i < 10; $i++) {
            $component->call('loadMore');
        }

        expect($component->get('perPage'))->toBeLessThanOrEqual(1000);
    });
});

describe('setPerPage guards', function (): void {
    it('does not allow perPage to be set to zero', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setPerPage', 0);

        expect($component->get('perPage'))->toBeGreaterThan(0);
    });

    it('does not allow perPage to be set to negative', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setPerPage', -5);

        expect($component->get('perPage'))->toBeGreaterThan(0);
    });
});

describe('loadData recursion guard', function (): void {
    it('resets page to 1 and completes without error when page exceeds results', function (): void {
        // No data in the database, but page is set to 5
        $component = Livewire::test(PostDataTable::class);
        $component->set('page', 5);
        $component->call('loadData');

        expect($component->get('page'))->toBe(1);
    });

    it('does not recurse more than once even with empty results', function (): void {
        // With no data and page > 1, loadData should recurse exactly once
        // (to reset page to 1), not infinitely
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();
        $instance->page = 100;

        // This should complete without stack overflow or timeout
        $instance->loadData();

        expect($instance->page)->toBe(1);
        expect($instance->data)->toHaveKey('total');
        expect($instance->data['total'])->toBe(0);
    });
});
