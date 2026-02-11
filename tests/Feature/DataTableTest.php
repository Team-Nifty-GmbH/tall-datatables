<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Livewire\SelectablePostDataTable;
use Tests\Fixtures\Livewire\UserDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('DataTable Initialization', function (): void {
    it('can mount with a valid model', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->instance())->toBeInstanceOf(PostDataTable::class);
        expect($component->get('initialized'))->toBeFalse();
    });

    it('initializes model key name on mount', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('modelKeyName'))->toBe('id');
        expect($component->get('modelTable'))->toBe('posts');
    });

    it('sets column labels on mount', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('colLabels'))
            ->toBeArray()
            ->toHaveKey('title')
            ->toHaveKey('content');
    });

    it('can mount user datatable', function (): void {
        $component = Livewire::test(UserDataTable::class);

        expect($component->get('modelKeyName'))->toBe('id');
        expect($component->get('modelTable'))->toBe('users');
        expect($component->get('enabledCols'))
            ->toBeArray()
            ->toContain('name')
            ->toContain('email');
    });

    it('initializes with default values', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(15);
        expect($component->get('page'))->toBe('1');
        expect($component->get('orderAsc'))->toBeTrue();
        expect($component->get('search'))->toBe('');
        expect($component->get('userFilters'))->toBe([]);
        expect($component->get('selected'))->toBe([]);
    });

    it('sets correct default filter properties', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('isFilterable'))->toBeTrue();
        expect($component->get('hasSidebar'))->toBeTrue();
        expect($component->get('hasHead'))->toBeTrue();
        expect($component->get('hasInfiniteScroll'))->toBeFalse();
        expect($component->get('hasNoRedirect'))->toBeFalse();
    });
});

describe('Data Loading', function (): void {
    it('can load data', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);
        createTestPost(['user_id' => $this->user->getKey()]);
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('data'))->toBeArray();
        expect($component->get('initialized'))->toBeTrue();
        expect($component->get('data.total'))->toBe(3);
        expect(count($component->get('data.data')))->toBe(3);
    });

    it('loads paginated data with correct structure', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        $data = $component->get('data');

        expect($data)
            ->toBeArray()
            ->toHaveKey('data')
            ->toHaveKey('total')
            ->toHaveKey('per_page')
            ->toHaveKey('current_page')
            ->toHaveKey('last_page');

        expect($data['total'])->toBe(20);
        expect($data['per_page'])->toBe(10);
        expect($data['current_page'])->toBe(1);
        expect($data['last_page'])->toBe(2);
        expect(count($data['data']))->toBe(10);
    });

    it('loads empty results gracefully', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('data'))->toBeArray();
        expect($component->get('data.total'))->toBe(0);
        expect($component->get('data.data'))->toBe([]);
        expect($component->get('initialized'))->toBeTrue();
    });

    it('returns correct data columns', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test Title', 'content' => 'Test Content']);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $firstRow = $component->get('data.data.0');

        expect($firstRow)
            ->toHaveKey('id')
            ->toHaveKey('title')
            ->toHaveKey('content')
            ->toHaveKey('href');
        expect($firstRow['title'])->toBe('Test Title');
        expect($firstRow['content'])->toBe('Test Content');
    });

    it('includes href when model implements InteractsWithDataTables', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test Post']);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $firstRow = $component->get('data.data.0');

        expect($firstRow['href'])->toBe('/posts/' . $post->getKey());
    });
});

describe('Pagination', function (): void {
    beforeEach(function (): void {
        for ($i = 0; $i < 30; $i++) {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post ' . ($i + 1)]);
        }
    });

    it('can go to a specific page', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 2);

        expect($component->get('page'))->toBe(2);
        expect($component->get('data.current_page'))->toBe(2);
    });

    it('can navigate through multiple pages', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect($component->get('data.current_page'))->toBe(1);

        $component->call('gotoPage', 2);
        expect($component->get('data.current_page'))->toBe(2);

        $component->call('gotoPage', 3);
        expect($component->get('data.current_page'))->toBe(3);

        $component->call('gotoPage', 1);
        expect($component->get('data.current_page'))->toBe(1);
    });

    it('can change items per page', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('setPerPage', 25);

        expect($component->get('perPage'))->toBe(25);
        expect($component->get('data.per_page'))->toBe(25);
        expect(count($component->get('data.data')))->toBe(25);
    });

    it('adjusts current page when per page increases past total', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 3);

        expect($component->get('page'))->toBe(3);

        $component->call('setPerPage', 50);

        expect($component->get('page'))->toBeLessThanOrEqual(1);
        expect(count($component->get('data.data')))->toBe(30);
    });

    it('can load more items (infinite scroll)', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect(count($component->get('data.data')))->toBe(10);

        $component->call('loadMore');

        expect($component->get('perPage'))->toBe(20);
        expect(count($component->get('data.data')))->toBe(20);

        $component->call('loadMore');

        expect($component->get('perPage'))->toBe(40);
        expect(count($component->get('data.data')))->toBe(30);
    });

    it('correctly shows pagination links', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect($component->get('data.links'))->toBeArray();
        expect(count($component->get('data.links')))->toBeGreaterThan(0);
    });
});

describe('Sorting', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha Post', 'created_at' => now()->subDays(3)]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Zulu Post', 'created_at' => now()->subDays(1)]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Mike Post', 'created_at' => now()->subDays(2)]);
    });

    it('can sort by column ascending', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title');

        expect($component->get('userOrderBy'))->toBe('title');
        expect($component->get('userOrderAsc'))->toBeTrue();

        $titles = array_column($component->get('data.data'), 'title');
        expect($titles)->toBe(['Alpha Post', 'Mike Post', 'Zulu Post']);
    });

    it('toggles sort direction on same column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title')
            ->call('sortTable', 'title');

        expect($component->get('userOrderBy'))->toBe('title');
        expect($component->get('userOrderAsc'))->toBeFalse();

        $titles = array_column($component->get('data.data'), 'title');
        expect($titles)->toBe(['Zulu Post', 'Mike Post', 'Alpha Post']);
    });

    it('keeps descending when changing column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title')
            ->call('sortTable', 'title')
            ->call('sortTable', 'created_at');

        expect($component->get('userOrderBy'))->toBe('created_at');
        expect($component->get('userOrderAsc'))->toBeFalse();
    });

    it('defaults to descending by primary key without explicit sort', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('orderBy'))->toBe('');
        expect($component->get('orderAsc'))->toBeTrue();

        $ids = array_column($component->get('data.data'), 'id');
        expect($ids)->toBe([3, 2, 1]);
    });
});

describe('Filtering', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published Post', 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Draft Post', 'is_published' => false]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Another Draft', 'is_published' => false]);
    });

    it('can apply user filters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    [
                        'column' => 'is_published',
                        'operator' => '=',
                        'value' => true,
                    ],
                ],
            ])
            ->call('applyUserFilters');

        expect($component->get('page'))->toBe('1');
        expect($component->get('loadedFilterId'))->toBeNull();
    });

    it('filters data correctly by boolean value', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    [
                        'column' => 'is_published',
                        'operator' => '=',
                        'value' => true,
                    ],
                ],
            ])
            ->call('loadData');

        expect($component->get('data.total'))->toBe(1);
        expect($component->get('data.data.0.title'))->toBe('Published Post');
    });

    it('can filter by title using like operator', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    [
                        'column' => 'title',
                        'operator' => 'like',
                        'value' => '%Draft%',
                    ],
                ],
            ])
            ->call('loadData');

        expect($component->get('data.total'))->toBe(2);
    });

    it('can apply OR filters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    [
                        'column' => 'title',
                        'operator' => '=',
                        'value' => 'Published Post',
                    ],
                ],
                [
                    [
                        'column' => 'title',
                        'operator' => '=',
                        'value' => 'Draft Post',
                    ],
                ],
            ])
            ->call('loadData');

        expect($component->get('data.total'))->toBe(2);
    });

    it('updates filters when userFilters changes', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('userFilters', [
                [
                    [
                        'column' => 'is_published',
                        'operator' => '=',
                        'value' => true,
                    ],
                ],
            ]);

        expect($component->get('loadedFilterId'))->toBeNull();
    });

    it('resets selected on startSearch', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('selected', [1, 2, 3])
            ->call('startSearch');

        expect($component->get('selected'))->toBe([]);
        expect($component->get('page'))->toBe('1');
    });

    it('resets page on applyUserFilters', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 2)
            ->set('userFilters', [
                [
                    [
                        'column' => 'is_published',
                        'operator' => '=',
                        'value' => false,
                    ],
                ],
            ])
            ->call('applyUserFilters');

        expect($component->get('page'))->toBe('1');
    });
});

describe('Column Configuration', function (): void {
    it('returns available columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $availableCols = $component->instance()->getAvailableCols();

        expect($availableCols)
            ->toBeArray()
            ->toContain('title')
            ->toContain('content')
            ->toContain('id')
            ->toContain('is_published');
    });

    it('returns column labels in human readable format', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $colLabels = $component->instance()->getColLabels();

        expect($colLabels)
            ->toBeArray()
            ->toHaveKey('title')
            ->toHaveKey('content');
        expect($colLabels['title'])->toBe('Title');
        expect($colLabels['content'])->toBe('Content');
    });

    it('returns enabled columns', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('enabledCols'))
            ->toBeArray()
            ->toContain('title')
            ->toContain('content');
    });

    it('can store column layout', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('storeColLayout', ['id', 'title']);

        expect($component->get('enabledCols'))
            ->toBe(['id', 'title']);
    });

    it('reloads data when adding columns', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $initialDataCount = count($component->get('data.data'));

        $component->call('storeColLayout', ['id', 'title', 'content', 'is_published']);

        expect($component->get('enabledCols'))->toContain('is_published');
    });
});

describe('Relation Columns', function (): void {
    beforeEach(function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);
    });

    it('can display relation columns', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class);

        expect($component->get('enabledCols'))
            ->toContain('title')
            ->toContain('user.name')
            ->toContain('user.email');
    });

    it('generates correct labels for relation columns', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class);
        $colLabels = $component->instance()->getColLabels();

        expect($colLabels)
            ->toHaveKey('title')
            ->toHaveKey('user.name')
            ->toHaveKey('user.email');
    });

    it('loads relation data correctly', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class)
            ->call('loadData');

        $firstRow = $component->get('data.data.0');

        expect($firstRow)
            ->toHaveKey('user.name')
            ->toHaveKey('user.email');
        expect($firstRow['user.name'])->toBe($this->user->name);
        expect($firstRow['user.email'])->toBe($this->user->email);
    });
});

describe('Selection', function (): void {
    beforeEach(function (): void {
        for ($i = 0; $i < 5; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }
    });

    it('can select individual rows', function (): void {
        $post = Post::first();

        $component = Livewire::test(SelectablePostDataTable::class)
            ->call('loadData')
            ->set('selected', [$post->getKey()]);

        expect($component->get('selected'))
            ->toHaveCount(1)
            ->toContain($post->getKey());
    });

    it('can select multiple rows', function (): void {
        $posts = Post::take(3)->pluck('id')->toArray();

        $component = Livewire::test(SelectablePostDataTable::class)
            ->call('loadData')
            ->set('selected', $posts);

        expect($component->get('selected'))
            ->toHaveCount(3)
            ->toBe($posts);
    });

    it('can select all with wildcard', function (): void {
        $component = Livewire::test(SelectablePostDataTable::class)
            ->call('loadData')
            ->set('selected', ['*']);

        expect($component->get('selected'))
            ->toContain('*');
    });

    it('can exclude items from wildcard selection', function (): void {
        $post = Post::first();

        $component = Livewire::test(SelectablePostDataTable::class)
            ->call('loadData')
            ->set('selected', ['*'])
            ->set('wildcardSelectExcluded', [$post->getKey()]);

        expect($component->get('wildcardSelectExcluded'))
            ->toHaveCount(1)
            ->toContain($post->getKey());
    });

    it('wildcard selection expands to all IDs on loadData', function (): void {
        $component = Livewire::test(SelectablePostDataTable::class)
            ->set('selected', ['*'])
            ->call('loadData');

        $selected = $component->get('selected');

        expect($selected)->toContain('*');
        expect(count($selected))->toBe(6);
    });

    it('isSelectable property is respected', function (): void {
        $component = Livewire::test(PostDataTable::class);
        expect($component->get('isSelectable'))->toBeTrue();

        $selectableComponent = Livewire::test(SelectablePostDataTable::class);
        expect($selectableComponent->get('isSelectable'))->toBeTrue();
    });
});

describe('Configuration', function (): void {
    it('returns config array with all required keys', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $config = $component->instance()->getConfig();

        expect($config)
            ->toBeArray()
            ->toHaveKey('enabledCols')
            ->toHaveKey('availableCols')
            ->toHaveKey('colLabels')
            ->toHaveKey('selectable')
            ->toHaveKey('formatters')
            ->toHaveKey('aggregatable')
            ->toHaveKey('leftAppend')
            ->toHaveKey('rightAppend')
            ->toHaveKey('topAppend')
            ->toHaveKey('bottomAppend')
            ->toHaveKey('searchRoute')
            ->toHaveKey('operatorLabels');
    });

    it('returns operator labels with all required operators', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $operatorLabels = $component->instance()->getOperatorLabels();

        expect($operatorLabels)
            ->toBeArray()
            ->toHaveKey('like')
            ->toHaveKey('not like')
            ->toHaveKey('is null')
            ->toHaveKey('is not null')
            ->toHaveKey('between')
            ->toHaveKey('and')
            ->toHaveKey('sum')
            ->toHaveKey('avg')
            ->toHaveKey('min')
            ->toHaveKey('max');
    });

    it('returns formatters from model casts', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $formatters = $component->instance()->getFormatters();

        expect($formatters)->toBeArray();
    });
});

describe('Soft Deletes', function (): void {
    it('does not include soft deleted by default', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('withSoftDeletes'))->toBeFalse();
    });

    it('can enable soft deletes', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('withSoftDeletes', true);

        expect($component->get('withSoftDeletes'))->toBeTrue();
    });

    it('does not show soft deleted posts by default', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost->delete();

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('data.total'))->toBe(1);
    });

    it('shows soft deleted posts when enabled', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost->delete();

        $component = Livewire::test(PostDataTable::class)
            ->set('withSoftDeletes', true)
            ->call('loadData');

        expect($component->get('data.total'))->toBe(2);
    });
});

describe('Session Filter', function (): void {
    it('can forget session filter', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('forgetSessionFilter');

        expect($component->get('sessionFilter'))->toBe([]);
    });

    it('sessionFilter is empty by default', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('sessionFilter'))->toBe([]);
    });
});

describe('Load Filter', function (): void {
    it('can load filter properties', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadFilter', ['perPage' => 50, 'orderBy' => 'title']);

        expect($component->get('perPage'))->toBe(50);
        expect($component->get('orderBy'))->toBe('title');
    });

    it('can load multiple filter properties at once', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadFilter', [
                'perPage' => 25,
                'orderBy' => 'created_at',
                'userOrderBy' => 'title',
                'userOrderAsc' => false,
            ]);

        expect($component->get('perPage'))->toBe(25);
        expect($component->get('orderBy'))->toBe('created_at');
        expect($component->get('userOrderBy'))->toBe('title');
        expect($component->get('userOrderAsc'))->toBeFalse();
    });

    it('does not load empty filter', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 15)
            ->call('loadFilter', []);

        expect($component->get('perPage'))->toBe(15);
    });

    it('reloads data when initialized', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('initialized'))->toBeTrue();

        $component->call('loadFilter', ['perPage' => 5]);

        expect($component->get('perPage'))->toBe(5);
    });
});

describe('Exporting', function (): void {
    it('isExportable is true by default', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('isExportable'))->toBeTrue();
    });

    it('returns exportable columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $exportableCols = $component->instance()->getExportableColumns();

        expect($exportableCols)
            ->toBeArray()
            ->toContain('title')
            ->toContain('content');
    });
});

describe('View Data', function (): void {
    it('passes correct data to view', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($viewData)
            ->toBeArray()
            ->toHaveKey('searchable')
            ->toHaveKey('componentAttributes')
            ->toHaveKey('rowAttributes')
            ->toHaveKey('cellAttributes')
            ->toHaveKey('rowActions')
            ->toHaveKey('tableActions')
            ->toHaveKey('selectedActions')
            ->toHaveKey('modelName')
            ->toHaveKey('showFilterInputs')
            ->toHaveKey('layout')
            ->toHaveKey('useWireNavigate')
            ->toHaveKey('colLabels')
            ->toHaveKey('allowSoftDeletes');
    });

    it('returns correct model name', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($viewData['modelName'])->toBe('Post');
    });
});

describe('Data Attributes', function (): void {
    it('returns component attributes as ComponentAttributeBag', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getComponentAttributes');
        $attributes = $reflection->invoke($instance);

        expect($attributes)->toBeInstanceOf(Illuminate\View\ComponentAttributeBag::class);
    });

    it('returns cell attributes as ComponentAttributeBag', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getCellAttributes');
        $attributes = $reflection->invoke($instance);

        expect($attributes)->toBeInstanceOf(Illuminate\View\ComponentAttributeBag::class);
    });

    it('returns row attributes as ComponentAttributeBag', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getRowAttributes');
        $attributes = $reflection->invoke($instance);

        expect($attributes)->toBeInstanceOf(Illuminate\View\ComponentAttributeBag::class);
    });
});

describe('Session Cache', function (): void {
    it('does not cache page in session to prevent stale pagination', function (): void {
        config(['tall-datatables.should_cache' => true]);

        for ($i = 0; $i < 30; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 3);

        expect($component->get('page'))->toBe(3);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . $component->instance()->getCacheKey();
        $cached = session()->get($cacheKey);

        expect($cached)->toBeArray();
        expect($cached)->not->toHaveKey('page');
        expect($cached)->toHaveKey('perPage');
        expect($cached)->toHaveKey('search');
        expect($cached)->toHaveKey('userFilters');
    });

    it('caches other filter properties in session', function (): void {
        config(['tall-datatables.should_cache' => true]);

        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 25)
            ->set('search', 'test search')
            ->call('loadData')
            ->call('sortTable', 'title');

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . $component->instance()->getCacheKey();
        $cached = session()->get($cacheKey);

        expect($cached)->toBeArray();
        expect($cached['perPage'])->toBe(25);
        expect($cached['search'])->toBe('test search');
        expect($cached['userOrderBy'])->toBe('title');
    });
});

describe('Saved Filters', function (): void {
    it('preserves loadedFilterId after loading a saved filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $savedFilter = DatatableUserSetting::create([
            'name' => 'Published Only',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [
                        [
                            'column' => 'is_published',
                            'operator' => '=',
                            'value' => true,
                        ],
                    ],
                ],
                'perPage' => 15,
            ],
        ]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        expect($component->get('savedFilters'))->toHaveCount(1);

        // Select the saved filter and load it
        $component
            ->set('loadedFilterId', $savedFilter->getKey())
            ->call('loadSavedFilter');

        // Verify userFilters were applied from the saved filter
        expect($component->get('userFilters'))->toBe([
            [
                [
                    'column' => 'is_published',
                    'operator' => '=',
                    'value' => true,
                ],
            ],
        ]);

        // Simulate the entangle sync: frontend sends back the same userFilters
        // This triggers updatedUserFilters() which should NOT reset loadedFilterId
        $component->set('userFilters', [
            [
                [
                    'column' => 'is_published',
                    'operator' => '=',
                    'value' => true,
                ],
            ],
        ]);

        // loadedFilterId should still be set to the saved filter
        expect($component->get('loadedFilterId'))->toBe($savedFilter->getKey());
    });

    it('clears loadedFilterId when user manually changes filters', function (): void {
        $savedFilter = DatatableUserSetting::create([
            'name' => 'Published Only',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [
                        [
                            'column' => 'is_published',
                            'operator' => '=',
                            'value' => true,
                        ],
                    ],
                ],
                'perPage' => 15,
            ],
        ]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('loadedFilterId', $savedFilter->getKey())
            ->call('loadSavedFilter');

        expect($component->get('loadedFilterId'))->toBe($savedFilter->getKey());

        // Simulate the entangle sync (same userFilters come back from frontend)
        $component->set('userFilters', [
            [
                [
                    'column' => 'is_published',
                    'operator' => '=',
                    'value' => true,
                ],
            ],
        ]);

        // loadedFilterId should still be set after entangle sync
        expect($component->get('loadedFilterId'))->toBe($savedFilter->getKey());

        // User manually changes filters (different from saved filter)
        $component->set('userFilters', [
            [
                [
                    'column' => 'title',
                    'operator' => 'like',
                    'value' => '%test%',
                ],
            ],
        ]);

        // loadedFilterId should be cleared because filters were manually changed
        expect($component->get('loadedFilterId'))->toBeNull();
    });

    it('loads saved filters from database on mount', function (): void {
        DatatableUserSetting::create([
            'name' => 'Filter A',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [['column' => 'is_published', 'operator' => '=', 'value' => true]],
                ],
            ],
        ]);

        DatatableUserSetting::create([
            'name' => 'Filter B',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [['column' => 'title', 'operator' => 'like', 'value' => '%test%']],
                ],
            ],
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('savedFilters'))->toHaveCount(2);
    });

    it('provides correctly structured options for saved filter dropdown', function (): void {
        // Create 3 filters: first and third have userFilters, second has empty userFilters
        DatatableUserSetting::create([
            'name' => 'Filter A',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [['column' => 'is_published', 'operator' => '=', 'value' => true]],
                ],
            ],
        ]);

        DatatableUserSetting::create([
            'name' => 'Empty Filter',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [],
            ],
        ]);

        DatatableUserSetting::create([
            'name' => 'Filter C',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'userFilters' => [
                    [['column' => 'title', 'operator' => 'like', 'value' => '%draft%']],
                ],
            ],
        ]);

        $component = Livewire::test(PostDataTable::class);

        // Build the options the same way the blade template does
        $options = collect($component->get('savedFilters'))
            ->filter(fn (array $savedFilter) => data_get($savedFilter, 'settings.userFilters', false))
            ->map(function (array $savedFilter) {
                return [
                    'label' => $savedFilter['name'],
                    'value' => $savedFilter['id'],
                ];
            })
            ->values()
            ->toArray();

        // Should have 2 options (empty filter is excluded)
        expect($options)->toHaveCount(2);

        // Keys should be sequential (0, 1) not (0, 2) - important for JSON encoding
        expect(array_keys($options))->toBe([0, 1]);

        // Each option should have label and value
        expect($options[0])->toHaveKey('label');
        expect($options[0])->toHaveKey('value');
        expect($options[0]['label'])->toBe('Filter A');
        expect($options[1]['label'])->toBe('Filter C');
    });
});
