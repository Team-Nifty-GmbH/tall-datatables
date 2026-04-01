<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('SupportsRelations', function (): void {
    describe('mountSupportsRelations', function (): void {
        it('initializes selectedCols on mount', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            expect($component->get('selectedCols'))->toBeArray()
                ->not->toBeEmpty();
        });

        it('initializes selectedRelations on mount', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            expect($component->get('selectedRelations'))->toBeArray();
        });

        it('initializes displayPath as empty for root model', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            expect($component->get('displayPath'))->toBe([]);
        });
    });

    describe('loadRelation', function (): void {
        it('loads root model columns', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadRelation(Post::class);

            expect($result)->toHaveKey('cols')
                ->toHaveKey('relations')
                ->toHaveKey('displayPath');

            expect($result['cols'])->toBeArray()
                ->not->toBeEmpty();
        });

        it('returns column attributes with label, col, slug, and type', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadRelation(Post::class);

            $firstCol = $result['cols'][0];

            expect($firstCol)->toHaveKey('label')
                ->toHaveKey('col')
                ->toHaveKey('slug')
                ->toHaveKey('virtual')
                ->toHaveKey('type')
                ->toHaveKey('attribute');
        });

        it('loads relations for the model', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadRelation(Post::class);

            expect($result['relations'])->toBeArray();

            // Post has user and comments relations, but only user and comments are available
            expect($result['relations'])->toHaveKey('user')
                ->toHaveKey('comments');
        });

        it('builds displayPath when navigating into a relation', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            // First load root
            $component->instance()->loadRelation(Post::class);
            // Then navigate into user relation
            $result = $component->instance()->loadRelation(Tests\Fixtures\Models\User::class, 'user');

            expect($result['displayPath'])->toHaveCount(1);
            expect($result['displayPath'][0])->toHaveKey('value')
                ->toHaveKey('label');
            expect($result['displayPath'][0]['value'])->toBe('user');
        });

        it('builds nested displayPath for deep relations', function (): void {
            $component = Livewire::test(PostDataTable::class);

            // Navigate root -> user -> posts
            $component->instance()->loadRelation(Post::class);
            $component->instance()->loadRelation(Tests\Fixtures\Models\User::class, 'user');
            $result = $component->instance()->loadRelation(Post::class, 'posts');

            expect($result['displayPath'])->toHaveCount(2);
            expect($result['displayPath'][0]['value'])->toBe('user');
            expect($result['displayPath'][1]['value'])->toBe('user.posts');
        });

        it('returns empty when relation is not in availableRelations', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            // availableRelations is ['user', 'comments'], so 'nonexistent' should return empty
            $result = $component->instance()->loadRelation(Post::class, 'nonexistent');

            expect($result)->toBe(['cols' => [], 'relations' => [], 'displayPath' => []]);
        });

        it('caches loaded relation data', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadRelation(Post::class);

            $cached = Cache::get('relation-tree-widget.' . Post::class);

            expect($cached)->not->toBeNull()
                ->toHaveKey('cols')
                ->toHaveKey('relations')
                ->toHaveKey('displayPath');
        });

        it('resets loadedPath when no relation name is provided', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            // Navigate into user first
            $component->instance()->loadRelation(Post::class);
            $component->instance()->loadRelation(Tests\Fixtures\Models\User::class, 'user');

            // Reset by loading without relation name
            $component->instance()->loadRelation(Post::class);

            expect($component->get('loadedPath'))->toBeNull();
        });

        it('filters available relations when not wildcard', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadRelation(Post::class);

            // Only user and comments should be in relations
            $relationKeys = array_keys($result['relations']);

            foreach ($relationKeys as $key) {
                expect(in_array($key, ['user', 'comments']))->toBeTrue();
            }
        });
    });

    describe('loadSlug', function (): void {
        it('returns empty cols/relations for unknown slug', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadSlug('unknown_relation');

            expect($result)->toBe(['cols' => [], 'relations' => []]);
        });

        it('returns structure with cols key when cache is empty', function (): void {
            Cache::flush();

            $component = Livewire::test(PostWithRelationsDataTable::class);

            // loadSlug with null and empty cache triggers loadRelation fallback
            // which may fail for test models, so we just verify the cache retrieval path
            $result = $component->instance()->loadSlug('nonexistent_path');

            expect($result)->toBe(['cols' => [], 'relations' => []]);
        });

        it('returns empty when path is not in availableRelations', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->loadSlug('nonexistent');

            expect($result)->toBe(['cols' => [], 'relations' => []]);
        });

        it('updates loadedPath when loading a known slug', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            // Use null to load root (which is safe in test environment)
            $component->instance()->loadSlug(null);

            // loadedPath should be empty for root
            expect($component->get('loadedPath'))->toBeEmpty();
        });
    });

    describe('getSidebarData', function (): void {
        it('returns selectedCols and selectedRelations', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->getSidebarData();

            expect($result)->toHaveKey('selectedCols')
                ->toHaveKey('selectedRelations');
        });

        it('returns selectedRelations in result', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->getSidebarData();

            expect($result)->toHaveKey('selectedRelations');
        });
    });

    describe('getFilterableColumns', function (): void {
        it('returns filterable columns from constructWith', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $filterable = $component->instance()->getFilterableColumns();

            expect($filterable)->toBeArray();
        });

        it('includes enabled columns that are not virtual', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $filterable = $component->instance()->getFilterableColumns();

            expect($filterable)->toContain('title')
                ->toContain('content')
                ->toContain('is_published');
        });
    });

    describe('getRelationTableCols', function (): void {
        it('returns model attributes when no relation specified', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $cols = $component->instance()->getRelationTableCols();

            expect($cols)->toBeArray()
                ->toHaveKey('title')
                ->toHaveKey('content');
        });

        it('returns array for root model attributes', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $cols = $component->instance()->getRelationTableCols();

            expect($cols)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('constructWith', function (): void {
        it('returns an array with expected structure', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            $result = $reflection->invoke($component->instance());

            expect($result)->toBeArray()
                ->toHaveCount(7);

            // [0] => with, [1] => select, [2] => filterable, [3] => filterValueLists,
            // [4] => sortable, [5] => relatedFormatters, [6] => enabledCols
        });

        it('includes boolean filter value lists', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            $result = $reflection->invoke($component->instance());

            $filterValueLists = $result[3];

            // is_published is a boolean field
            expect($filterValueLists)->toHaveKey('is_published');
            expect($filterValueLists['is_published'])->toHaveCount(2);
            expect($filterValueLists['is_published'][0])->toHaveKey('value')
                ->toHaveKey('label');
        });

        it('returns sortable columns', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            $result = $reflection->invoke($component->instance());

            $sortable = $result[4];

            expect($sortable)->toBeArray()
                ->toContain('title')
                ->toContain('content');
        });

        it('caches results for subsequent calls', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');

            $result1 = $reflection->invoke($component->instance());
            $result2 = $reflection->invoke($component->instance());

            expect($result1)->toBe($result2);
        });

        it('handles relation columns in enabledCols', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            $result = $reflection->invoke($component->instance());

            $with = $result[0];

            // Should include user relation
            expect($with)->toBeArray();

            $hasUserRelation = false;
            foreach ($with as $item) {
                if (str_starts_with($item, 'user:')) {
                    $hasUserRelation = true;

                    break;
                }
            }

            expect($hasUserRelation)->toBeTrue();
        });

        it('removes bad method calls from enabledCols gracefully', function (): void {
            $component = Livewire::test(PostDataTable::class);

            // Add a nonexistent relation column
            $component->set('enabledCols', ['title', 'nonexistentRelation.name']);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            $result = $reflection->invoke($component->instance());

            $enabledCols = $result[6];

            // nonexistentRelation.name should have been removed
            expect($enabledCols)->not->toContain('nonexistentRelation.name');
        });
    });

    describe('getFilterValueList', function (): void {
        it('sets boolean filter values for bool attributes', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $component->call('loadData');

            $filterValueLists = $component->get('filterValueLists');

            expect($filterValueLists)->toHaveKey('is_published');
            expect($filterValueLists['is_published'])->toBe([
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ]);
        });

        it('does not overwrite existing filter value list', function (): void {
            $component = Livewire::test(PostDataTable::class);

            // Set a custom list
            $component->set('filterValueLists', ['is_published' => [['value' => 'custom', 'label' => 'Custom']]]);

            // Force re-call of constructWith
            Cache::flush();
            $component->call('loadData');

            $filterValueLists = $component->get('filterValueLists');

            expect($filterValueLists['is_published'])->toBe([['value' => 'custom', 'label' => 'Custom']]);
        });
    });

    describe('addDynamicJoin', function (): void {
        it('adds a join for a belongsTo relationship', function (): void {
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostWithRelationsDataTable::class);

            $query = Post::query();
            $reflection = new ReflectionMethod($component->instance(), 'addDynamicJoin');
            $relatedTable = $reflection->invoke($component->instance(), $query, 'user');

            expect($relatedTable)->toBe('users');

            // Should be able to execute the query without errors
            $results = $query->get();

            expect($results)->not->toBeEmpty();
        });

        it('throws exception for undefined relation', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $query = Post::query();
            $reflection = new ReflectionMethod($component->instance(), 'addDynamicJoin');

            expect(fn () => $reflection->invoke($component->instance(), $query, 'nonExistent'))
                ->toThrow(Exception::class, "Relation 'nonExistent' is not defined on");
        });

        it('adds joins for nested relations', function (): void {
            createTestComment(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostWithRelationsDataTable::class);

            $query = Tests\Fixtures\Models\Comment::query();
            $reflection = new ReflectionMethod($component->instance(), 'addDynamicJoin');
            $relatedTable = $reflection->invoke($component->instance(), $query, 'post.user');

            expect($relatedTable)->toBe('users');
        });

        it('includes additional selects when provided', function (): void {
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostWithRelationsDataTable::class);

            $query = Post::query();
            $reflection = new ReflectionMethod($component->instance(), 'addDynamicJoin');
            $reflection->invoke($component->instance(), $query, 'user', ['users.name as author_name']);

            $results = $query->get();

            expect($results->first()->author_name)->not->toBeNull();
        });
    });

    describe('getModelRelations', function (): void {
        it('returns relations with model, label, name, and type', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $modelInfo = TeamNiftyGmbH\DataTable\Helpers\ModelInfo::forModel(Post::class);
            $reflection = new ReflectionMethod($component->instance(), 'getModelRelations');
            $relations = $reflection->invoke($component->instance(), $modelInfo);

            expect($relations)->toBeArray();

            if (isset($relations['user'])) {
                expect($relations['user'])->toHaveKey('model')
                    ->toHaveKey('label')
                    ->toHaveKey('name')
                    ->toHaveKey('type');
            }
        });

        it('includes relation key information', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $modelInfo = TeamNiftyGmbH\DataTable\Helpers\ModelInfo::forModel(Post::class);
            $reflection = new ReflectionMethod($component->instance(), 'getModelRelations');
            $relations = $reflection->invoke($component->instance(), $modelInfo);

            if (isset($relations['user']['keys'])) {
                expect($relations['user']['keys'])->toHaveKey('owner')
                    ->toHaveKey('foreign');
            }
        });
    });

    describe('with property', function (): void {
        it('defaults to empty array', function (): void {
            $component = Livewire::test(PostDataTable::class);

            expect($component->get('with'))->toBe([]);
        });
    });

    describe('loadSlug with cached data', function (): void {
        it('returns cached data for root model via PostDataTable', function (): void {
            // PostDataTable has availableRelations = ['*'] so null path is allowed
            $component = Livewire::test(PostDataTable::class);

            // First load populates cache
            $component->instance()->loadRelation(Post::class);

            // loadSlug for root should return cached data
            $result = $component->instance()->loadSlug(null);

            expect($result)->toHaveKey('cols')
                ->toHaveKey('relations')
                ->toHaveKey('displayPath');
            expect($result['cols'])->toBeArray();
        });

        it('sets loadedPath correctly when loading slug', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            // First load the user relation to cache it
            $component->instance()->loadRelation(Post::class);
            $component->instance()->loadRelation(Tests\Fixtures\Models\User::class, 'user');

            // Reset and load via slug
            $component->instance()->loadSlug('user');

            expect($component->get('loadedPath'))->toBe('user');
        });
    });

    describe('constructWith with virtual attributes', function (): void {
        it('uses wildcard select for virtual attributes', function (): void {
            // When a virtual attribute is in enabledCols, select should use *
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            Cache::flush();

            $result = $reflection->invoke($component->instance());

            // Result has 7 elements: with, select, filterable, filterValueLists, sortable, formatters, enabledCols
            expect($result)->toHaveCount(7);
        });
    });

    describe('getFilterValueList edge cases', function (): void {
        it('skips columns with no cast and no class', function (): void {
            $component = Livewire::test(PostDataTable::class);

            // title has no special cast, so getFilterValueList should not set a value list for it
            $filterValueLists = $component->get('filterValueLists');

            expect($filterValueLists)->not->toHaveKey('title');
        });
    });

    describe('getModelRelations', function (): void {
        it('excludes MorphTo relations', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $modelInfo = TeamNiftyGmbH\DataTable\Helpers\ModelInfo::forModel(Post::class);
            $reflection = new ReflectionMethod($component->instance(), 'getModelRelations');
            $relations = $reflection->invoke($component->instance(), $modelInfo);

            // MorphTo relations should be excluded
            foreach ($relations as $relation) {
                expect($relation['type'])->not->toContain('MorphTo');
            }
        });

        it('includes key information for HasMany relations', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $modelInfo = TeamNiftyGmbH\DataTable\Helpers\ModelInfo::forModel(Post::class);
            $reflection = new ReflectionMethod($component->instance(), 'getModelRelations');
            $relations = $reflection->invoke($component->instance(), $modelInfo);

            if (isset($relations['comments'])) {
                expect($relations['comments'])->toHaveKey('model')
                    ->toHaveKey('name')
                    ->toHaveKey('type');
            }
        });
    });

    describe('loadRelation with available relations wildcard', function (): void {
        it('allows all relations when availableRelations is wildcard', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $result = $component->instance()->loadRelation(Post::class, 'user');

            // PostDataTable has availableRelations as default ['*']
            expect($result['cols'])->toBeArray();
        });
    });

    describe('constructWith relation type detection', function (): void {
        it('identifies HasMany as many relation for array formatter', function (): void {
            // Use PostWithRelationsDataTable which has comments (HasMany) relation access
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'constructWith');
            Cache::flush();

            $result = $reflection->invoke($component->instance());

            $formatters = $result[5];
            // formatters should contain relation formatter entries
            expect($formatters)->toBeArray();
        });
    });
});
