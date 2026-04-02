<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Components\DataTableFilters;
use Tests\Fixtures\Models\Post;

describe('DataTableFilters Component', function (): void {
    beforeEach(function (): void {
        $this->user = createTestUser();
        $this->actingAs($this->user);
    });

    describe('instantiation', function (): void {
        it('creates component with required properties', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['name', 'email'],
                'cacheKey' => 'test-filters',
                'model' => Post::class,
            ]);

            expect($component->instance())->toBeInstanceOf(DataTableFilters::class);
        });

        it('accepts empty columns', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => [],
                'cacheKey' => 'test-filters-empty',
                'model' => Post::class,
            ]);

            expect($component->instance()->availableCols)->toBeEmpty();
        });
    });

    describe('locked properties', function (): void {
        it('has locked availableCols property', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title', 'content'],
                'cacheKey' => 'test-locked',
                'model' => Post::class,
            ]);

            $component->assertSet('availableCols', ['title', 'content']);
        });

        it('has locked cacheKey property', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'my-cache-key',
                'model' => Post::class,
            ]);

            $component->assertSet('cacheKey', 'my-cache-key');
        });

        it('has locked model property', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-model',
                'model' => Post::class,
            ]);

            $component->assertSet('model', Post::class);
        });
    });

    describe('addFilter', function (): void {
        it('appends a filter to the filters array', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title', 'content'],
                'cacheKey' => 'test-add-filter',
                'model' => Post::class,
            ]);

            $filter = ['column' => 'title', 'operator' => '=', 'value' => 'Test'];
            $component->call('addFilter', $filter);

            $filters = $component->get('filters');
            expect($filters)->toHaveCount(1);
            expect($filters[0])->toBe($filter);
        });

        it('appends multiple filters sequentially', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title', 'content'],
                'cacheKey' => 'test-multi-filter',
                'model' => Post::class,
            ]);

            $component
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'A'])
                ->call('addFilter', ['column' => 'content', 'operator' => 'like', 'value' => '%B%']);

            expect($component->get('filters'))->toHaveCount(2);
        });

        it('dispatches filters-changed with updated filters', function (): void {
            $filter = ['column' => 'title', 'operator' => 'like', 'value' => '%test%'];

            Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-dispatch-add',
                'model' => Post::class,
            ])
                ->call('addFilter', $filter)
                ->assertDispatched('filters-changed', fn ($name, $params) => count($params['filters']) === 1
                    && $params['filters'][0]['column'] === 'title'
                );
        });
    });

    describe('removeFilter', function (): void {
        it('removes a filter at a given index', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title', 'content'],
                'cacheKey' => 'test-remove',
                'model' => Post::class,
            ]);

            $component
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'A'])
                ->call('addFilter', ['column' => 'content', 'operator' => '=', 'value' => 'B'])
                ->call('removeFilter', 0);

            $filters = $component->get('filters');
            expect($filters)->toHaveCount(1);
            expect($filters[0]['column'])->toBe('content');
        });

        it('re-indexes filters after removal', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['a', 'b', 'c'],
                'cacheKey' => 'test-reindex',
                'model' => Post::class,
            ]);

            $component
                ->call('addFilter', ['column' => 'a', 'operator' => '=', 'value' => '1'])
                ->call('addFilter', ['column' => 'b', 'operator' => '=', 'value' => '2'])
                ->call('addFilter', ['column' => 'c', 'operator' => '=', 'value' => '3'])
                ->call('removeFilter', 1);

            $filters = $component->get('filters');
            expect(array_keys($filters))->toBe([0, 1]);
            expect($filters[0]['column'])->toBe('a');
            expect($filters[1]['column'])->toBe('c');
        });

        it('dispatches filters-changed after removal', function (): void {
            Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-dispatch-remove',
                'model' => Post::class,
            ])
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'X'])
                ->call('removeFilter', 0)
                ->assertDispatched('filters-changed');
        });
    });

    describe('clearFilters', function (): void {
        it('removes all filters', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-clear',
                'model' => Post::class,
            ]);

            $component
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'A'])
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'B'])
                ->call('clearFilters');

            expect($component->get('filters'))->toBe([]);
        });

        it('dispatches filters-changed with empty filters', function (): void {
            Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-clear-dispatch',
                'model' => Post::class,
            ])
                ->call('addFilter', ['column' => 'title', 'operator' => '=', 'value' => 'X'])
                ->call('clearFilters')
                ->assertDispatched('filters-changed', fn ($name, $params) => $params['filters'] === []);
        });
    });

    describe('getColumnType', function (): void {
        it('returns boolean for boolean cast column', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['is_published'],
                'cacheKey' => 'test-col-type-bool',
                'model' => Post::class,
            ]);

            $type = $component->instance()->getColumnType('is_published');

            expect($type)->toBe('boolean');
        });

        it('returns text for string column without cast', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-col-type-text',
                'model' => Post::class,
            ]);

            $type = $component->instance()->getColumnType('title');

            expect($type)->toBe('text');
        });
    });

    describe('savedFilters property', function (): void {
        it('defaults to empty array', function (): void {
            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-saved-default',
                'model' => Post::class,
            ]);

            $component->assertSet('savedFilters', []);
        });

        it('can be initialized with values', function (): void {
            $saved = [
                ['name' => 'Filter 1', 'filters' => [['column' => 'title', 'operator' => '=', 'value' => 'X']]],
            ];

            $component = Livewire::test(DataTableFilters::class, [
                'availableCols' => ['title'],
                'cacheKey' => 'test-saved-init',
                'model' => Post::class,
                'savedFilters' => $saved,
            ]);

            $component->assertSet('savedFilters', $saved);
        });
    });
});
