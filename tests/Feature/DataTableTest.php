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
        expect($component->get('initialized'))->toBeTrue();
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
        expect($component->get('page'))->toBe(1);
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

        expect($component->instance()->getDataForTesting())->toBeArray();
        expect($component->get('initialized'))->toBeTrue();
        expect($component->instance()->getDataForTesting()['total'])->toBe(3);
        expect(count($component->instance()->getDataForTesting()['data']))->toBe(3);
    });

    it('loads paginated data with correct structure', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();

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

        expect($component->instance()->getDataForTesting())->toBeArray();
        expect($component->instance()->getDataForTesting()['total'])->toBe(0);
        expect($component->instance()->getDataForTesting()['data'])->toBe([]);
        expect($component->get('initialized'))->toBeTrue();
    });

    it('returns correct data columns', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test Title', 'content' => 'Test Content']);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $firstRow = $component->instance()->getDataForTesting()['data'][0];

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

        $firstRow = $component->instance()->getDataForTesting()['data'][0];

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
        expect($component->instance()->getDataForTesting()['current_page'])->toBe(2);
    });

    it('can navigate through multiple pages', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['current_page'])->toBe(1);

        $component->call('gotoPage', 2);
        expect($component->instance()->getDataForTesting()['current_page'])->toBe(2);

        $component->call('gotoPage', 3);
        expect($component->instance()->getDataForTesting()['current_page'])->toBe(3);

        $component->call('gotoPage', 1);
        expect($component->instance()->getDataForTesting()['current_page'])->toBe(1);
    });

    it('can change items per page', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('setPerPage', 25);

        expect($component->get('perPage'))->toBe(25);
        expect($component->instance()->getDataForTesting()['per_page'])->toBe(25);
        expect(count($component->instance()->getDataForTesting()['data']))->toBe(25);
    });

    it('adjusts current page when per page increases past total', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData')
            ->call('gotoPage', 3);

        expect($component->get('page'))->toBe(3);

        // setPerPage resets page when needed and reloads
        $component->call('setPerPage', 50);
        expect($component->get('perPage'))->toBe(50);

        // After reload, all 30 items should be on page 1
        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(30);
        expect($data['per_page'])->toBe(50);
    });

    it('can load more items (infinite scroll)', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect(count($component->instance()->getDataForTesting()['data']))->toBe(10);

        $component->call('loadMore');

        expect($component->get('perPage'))->toBe(20);
        expect(count($component->instance()->getDataForTesting()['data']))->toBe(20);

        $component->call('loadMore');

        expect($component->get('perPage'))->toBe(40);
        expect(count($component->instance()->getDataForTesting()['data']))->toBe(30);
    });

    it('correctly shows pagination links', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['links'])->toBeArray();
        expect(count($component->instance()->getDataForTesting()['links']))->toBeGreaterThan(0);
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

        $titles = array_column($component->instance()->getDataForTesting()['data'], 'title');
        expect($titles)->toBe(['Alpha Post', 'Mike Post', 'Zulu Post']);
    });

    it('toggles sort direction on same column', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title')
            ->call('sortTable', 'title');

        expect($component->get('userOrderBy'))->toBe('title');
        expect($component->get('userOrderAsc'))->toBeFalse();

        $titles = array_column($component->instance()->getDataForTesting()['data'], 'title');
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

        $ids = array_column($component->instance()->getDataForTesting()['data'], 'id');
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

        expect($component->get('page'))->toBe(1);
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

        expect($component->instance()->getDataForTesting()['total'])->toBe(1);
        expect($component->instance()->getDataForTesting()['data'][0]['title'])->toBe('Published Post');
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

        expect($component->instance()->getDataForTesting()['total'])->toBe(2);
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

        expect($component->instance()->getDataForTesting()['total'])->toBe(2);
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
        expect($component->get('page'))->toBe(1);
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

        expect($component->get('page'))->toBe(1);
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

        $initialDataCount = count($component->instance()->getDataForTesting()['data']);

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

        $firstRow = $component->instance()->getDataForTesting()['data'][0];

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

    it('wildcard selection returns all records across all pages', function (): void {
        // Create 25 posts total (5 from Selection beforeEach + 20 here)
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $totalPosts = Post::count();
        expect($totalPosts)->toBe(25);

        $component = Livewire::test(SelectablePostDataTable::class)
            ->set('perPage', 10)
            ->set('selected', ['*'])
            ->call('loadData');

        $instance = $component->instance();

        // Test getSelectedModelsQuery - should return query for all 25 posts
        $queryReflection = new ReflectionMethod($instance, 'getSelectedModelsQuery');
        $query = $queryReflection->invoke($instance);
        expect($query->count())->toBe(25);

        // Test getSelectedModels - should return all 25 posts
        $modelsReflection = new ReflectionMethod($instance, 'getSelectedModels');
        $selectedModels = $modelsReflection->invoke($instance);
        expect($selectedModels)->toHaveCount(25);

        $selectedIds = $selectedModels->pluck('id')->sort()->values()->toArray();
        $allPostIds = Post::pluck('id')->sort()->values()->toArray();
        expect($selectedIds)->toBe($allPostIds);
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

        expect($component->instance()->getDataForTesting()['total'])->toBe(1);
    });

    it('shows soft deleted posts when enabled', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost = createTestPost(['user_id' => $this->user->getKey()]);
        $deletedPost->delete();

        $component = Livewire::test(PostDataTable::class)
            ->set('withSoftDeletes', true)
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['total'])->toBe(2);
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

describe('clearFiltersAndSort', function (): void {
    it('resets all filter and sort properties', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [[['column' => 'title', 'operator' => '=', 'value' => 'test']]])
            ->set('textFilters', [['title' => 'test']])
            ->set('userOrderBy', 'title')
            ->set('userOrderAsc', false)
            ->set('search', 'hello')
            ->call('loadData')
            ->call('clearFiltersAndSort');

        expect($component->get('userFilters'))->toBe([])
            ->and($component->get('textFilters'))->toBe([])
            ->and($component->get('userOrderBy'))->toBe('')
            ->and($component->get('userOrderAsc'))->toBeTrue()
            ->and($component->get('search'))->toBe('')
            ->and($component->get('groupBy'))->toBeNull()
            ->and($component->get('loadedFilterId'))->toBeNull();
    });
});

describe('forgetSessionFilter', function (): void {
    it('clears session filter and resets property', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $cacheKey = $component->instance()->getCacheKey();

        session()->put($cacheKey . '_query', 'some_filter');

        $component->call('forgetSessionFilter');

        expect(session()->has($cacheKey . '_query'))->toBeFalse()
            ->and($component->get('sessionFilter'))->toBe([]);
    });

    it('reloads data when loadData flag is true', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('forgetSessionFilter', true);

        expect($component->get('initialized'))->toBeTrue();
    });

    it('does not reload data when loadData flag is false', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('forgetSessionFilter', false);

        expect($component->get('sessionFilter'))->toBe([]);
    });
});

describe('formatFilterBadgeValue', function (): void {
    it('returns raw value when no formatter found', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $result = $component->instance()->formatFilterBadgeValue('title', 'test');

        expect($result)->toBe('test');
    });

    it('formats value using model casts', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $result = $component->instance()->formatFilterBadgeValue('price', '42.50');

        expect($result)->toBeString();
    });

    it('handles non-numeric values gracefully', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $result = $component->instance()->formatFilterBadgeValue('title', 'some text');

        expect($result)->toBe('some text');
    });
});

describe('removeFilter', function (): void {
    it('removes a filter at specific index', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Keep']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other']);

        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    ['column' => 'title', 'operator' => '=', 'value' => 'Keep'],
                    ['column' => 'price', 'operator' => '>', 'value' => 10],
                ],
            ])
            ->call('loadData')
            ->call('removeFilter', 0, 1);

        $filters = $component->get('userFilters');

        expect($filters[0])->toHaveCount(1)
            ->and($filters[0][0]['column'])->toBe('title');
    });

    it('removes empty groups after removing last filter', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'test']],
            ])
            ->call('loadData')
            ->call('removeFilter', 0, 0);

        expect($component->get('userFilters'))->toBe([]);
    });

    it('does nothing when filter index does not exist', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'test']],
            ])
            ->call('loadData')
            ->call('removeFilter', 5, 0);

        expect($component->get('userFilters'))->toHaveCount(1);
    });

    it('removes text filter entry from textFilters', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('textFilters', [['title' => 'hello']])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'like', 'value' => '%hello%', 'source' => 'text']],
            ])
            ->call('loadData')
            ->call('removeFilter', 0, 0);

        expect($component->get('userFilters'))->toBe([]);
    });
});

describe('removeFilterGroup', function (): void {
    it('removes entire filter group by index', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'A']],
                [['column' => 'title', 'operator' => '=', 'value' => 'B']],
            ])
            ->call('loadData')
            ->call('removeFilterGroup', 0);

        $filters = $component->get('userFilters');

        expect($filters)->toHaveCount(1)
            ->and($filters[0][0]['value'])->toBe('B');
    });

    it('does nothing when group index does not exist', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'A']],
            ])
            ->call('loadData')
            ->call('removeFilterGroup', 5);

        expect($component->get('userFilters'))->toHaveCount(1);
    });

    it('cleans up textFilters for text-source filters in removed group', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->set('textFilters', [['title' => 'hello']])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'like', 'value' => '%hello%', 'source' => 'text']],
            ])
            ->call('loadData')
            ->call('removeFilterGroup', 0);

        expect($component->get('userFilters'))->toBe([]);
    });
});

describe('removeTextFilterRow', function (): void {
    it('removes text filter row and rebuilds userFilters', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'hello', 0)
            ->call('setTextFilter', 'title', 'world', 1);

        expect($component->get('textFilters'))->toHaveCount(2);

        $component->call('removeTextFilterRow', 0);

        $textFilters = $component->get('textFilters');

        expect($textFilters)->toHaveCount(1);
    });
});

describe('setTextFilter with multi-value', function (): void {
    it('handles multi-value text filter at specific index', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'first', 0, 0)
            ->call('setTextFilter', 'title', 'second', 0, 1);

        $textFilters = $component->get('textFilters');

        expect($textFilters[0]['title'])->toBeArray()
            ->and($textFilters[0]['title'])->toContain('first')
            ->and($textFilters[0]['title'])->toContain('second');
    });

    it('removes multi-value entry when value is null', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'first', 0, 0)
            ->call('setTextFilter', 'title', 'second', 0, 1)
            ->call('setTextFilter', 'title', null, 0, 0);

        $textFilters = $component->get('textFilters');

        expect($textFilters[0]['title'])->toBe('second');
    });

    it('removes group when all text filters are cleared', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'hello', 0)
            ->call('setTextFilter', 'title', null, 0);

        expect($component->get('textFilters'))->toBe([]);
    });
});

describe('getParsedTextFilters', function (): void {
    it('returns text-source filters from userFilters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [
                    ['column' => 'title', 'operator' => 'like', 'value' => '%hello%', 'source' => 'text'],
                    ['column' => 'price', 'operator' => '>', 'value' => 10],
                ],
            ]);

        $parsed = $component->instance()->getParsedTextFilters();

        expect($parsed)->toHaveCount(1)
            ->and($parsed[0]['column'])->toBe('title');
    });

    it('strips LIKE wildcards from display value', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'like', 'value' => '%hello%', 'source' => 'text']],
            ]);

        $parsed = $component->instance()->getParsedTextFilters();

        expect($parsed[0]['value'])->toBe('hello');
    });

    it('returns empty collection when no text filters exist', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('userFilters', [
                [['column' => 'title', 'operator' => '=', 'value' => 'test']],
            ]);

        $parsed = $component->instance()->getParsedTextFilters();

        expect($parsed)->toHaveCount(0);
    });

    it('translates enum value for display', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->instance()->filterValueLists = [
            'is_published' => [
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ],
        ];

        $component->set('userFilters', [
            [['column' => 'is_published', 'operator' => '=', 'value' => 1, 'source' => 'text']],
        ]);

        $parsed = $component->instance()->getParsedTextFilters();

        expect($parsed[0]['value'])->toBe('Yes');
    });
});

describe('getGroupLabels', function (): void {
    it('returns all required group label keys', function (): void {
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
});

describe('getIslandData', function (): void {
    it('returns same data as getViewData', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $islandData = $instance->getIslandData();

        $reflection = new ReflectionMethod($instance, 'getViewData');
        $viewData = $reflection->invoke($instance);

        expect($islandData)->toBe($viewData);
    });
});

describe('forceRender', function (): void {
    it('is a no-op for backwards compatibility', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('forceRender');

        expect($component->get('initialized'))->toBeTrue();
    });
});

describe('reloadData', function (): void {
    it('reloads data when called', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Reloaded']);

        $component = Livewire::test(PostDataTable::class)
            ->call('reloadData');

        $data = $component->instance()->getDataForTesting();

        expect($data['total'])->toBe(1);
    });
});

describe('updatedSearch', function (): void {
    it('triggers startSearch on search update', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Findable']);

        $component = Livewire::test(PostDataTable::class)
            ->set('selected', [1, 2])
            ->set('search', 'Findable');

        expect($component->get('selected'))->toBe([])
            ->and($component->get('page'))->toBe(1);
    });
});

describe('dehydrate', function (): void {
    it('clears data on dehydrate', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $instance = $component->instance();
        $instance->dehydrate();

        expect($instance->data)->toBe([]);
    });
});

describe('compileActions', function (): void {
    it('returns empty row actions by default', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');
        $actions = $reflection->invoke($instance, 'row');

        expect($actions)->toBe([]);
    });

    it('returns empty table actions by default', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');
        $actions = $reflection->invoke($instance, 'table');

        expect($actions)->toBe([]);
    });

    it('caches actions per type', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileActions');

        $first = $reflection->invoke($instance, 'row');
        $second = $reflection->invoke($instance, 'row');

        expect($first)->toBe($second);
    });
});

describe('showRestoreButton', function (): void {
    it('returns false by default', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'showRestoreButton');
        $result = $reflection->invoke($instance);

        expect($result)->toBeFalse();
    });
});

describe('getSearchRoute', function (): void {
    it('returns empty string when no search route configured', function (): void {
        config(['tall-datatables.search_route' => null]);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getSearchRoute');
        $route = $reflection->invoke($instance);

        expect($route)->toBe('');
    });
});

describe('getTableFields', function (): void {
    it('returns non-virtual attributes', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getTableFields');
        $fields = $reflection->invoke($instance);

        expect($fields)->toBeInstanceOf(Illuminate\Support\Collection::class)
            ->and($fields->count())->toBeGreaterThan(0);
    });
});

describe('getIncludedRelations', function (): void {
    it('returns self entry for non-relation columns', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getIncludedRelations');
        $relations = $reflection->invoke($instance);

        expect($relations)->toHaveKey('self')
            ->and($relations['self']['model'])->toBe(Post::class);
    });

    it('returns relation entries for dot-notation columns', function (): void {
        $component = Livewire::test(PostWithRelationsDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'getIncludedRelations');
        $relations = $reflection->invoke($instance);

        expect($relations)->toHaveKey('user')
            ->and($relations['user']['model'])->toBe(Tests\Fixtures\Models\User::class);
    });
});

describe('applyUserFilters text filter cleanup', function (): void {
    it('removes orphaned textFilters entries', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData');

        $component->set('textFilters', [['title' => 'old', 'content' => 'removed']]);
        $component->set('userFilters', [
            [['column' => 'title', 'operator' => 'like', 'value' => '%old%', 'source' => 'text']],
        ]);

        $component->call('applyUserFilters');

        $textFilters = $component->get('textFilters');

        expect($textFilters)->not->toHaveKey('content');
    });
});

describe('updatedUserFilters', function (): void {
    it('does not apply filters when loadingFilter is true', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('loadingFilter', true)
            ->set('userFilters', [[['column' => 'title', 'operator' => '=', 'value' => 'x']]]);

        expect($component->get('loadingFilter'))->toBeFalse();
    });
});
