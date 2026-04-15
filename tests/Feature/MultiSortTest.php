<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    foreach (range(1, 5) as $i) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post {$i}",
        ]);
    }
});

describe('Multi-Sort', function (): void {
    describe('sortTable with empty string', function (): void {
        it('clears sort when called with empty string', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('sortTable', 'title', false);

            expect($component->get('userOrderBy'))->toBe('title');

            $component->call('sortTable', '', false);

            expect($component->get('userOrderBy'))->toBe('')
                ->and($component->get('userMultiSort'))->toBe([]);
        });
    });

    describe('sortTable with append', function (): void {
        it('sets primary sort when append is false', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false);

            expect($component->get('userOrderBy'))->toBe('title')
                ->and($component->get('userMultiSort'))->toBe([]);
        });

        it('clears multi-sort when append is false', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('sortTable', 'id', false);

            expect($component->get('userOrderBy'))->toBe('id')
                ->and($component->get('userMultiSort'))->toBe([]);
        });

        it('adds secondary sort when append is true', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true);

            expect($component->get('userOrderBy'))->toBe('title')
                ->and($component->get('userMultiSort'))->toHaveCount(1)
                ->and($component->get('userMultiSort')[0]['column'])->toBe('created_at')
                ->and($component->get('userMultiSort')[0]['asc'])->toBeTrue();
        });

        it('toggles direction of existing secondary sort', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('sortTable', 'created_at', true);

            expect($component->get('userMultiSort')[0]['asc'])->toBeFalse();
        });

        it('removes secondary sort on third append-click', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('sortTable', 'created_at', true)
                ->call('sortTable', 'created_at', true);

            expect($component->get('userMultiSort'))->toBe([]);
        });

        it('supports multiple secondary sorts', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('sortTable', 'id', true);

            expect($component->get('userMultiSort'))->toHaveCount(2);
        });

        it('toggles primary sort direction on shift+click of primary column', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'title', true);

            expect($component->get('userOrderAsc'))->toBeFalse()
                ->and($component->get('userMultiSort'))->toBe([]);
        });

        it('resets direction to ASC when switching primary sort column', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'title', false) // toggle to DESC
                ->call('sortTable', 'created_at', false); // switch column

            expect($component->get('userOrderBy'))->toBe('created_at')
                ->and($component->get('userOrderAsc'))->toBeTrue();
        });
    });

    describe('query building', function (): void {
        it('applies multi-sort to query without errors', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true);

            expect($component->get('data'))->toBeArray()
                ->and($component->get('initialized'))->toBeTrue();
        });
    });

    describe('persistence', function (): void {
        it('persists userMultiSort in saved filters', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('saveFilter', 'Multi Sort Filter');

            $savedFilters = $component->get('savedFilters');
            $settings = data_get($savedFilters, '0.settings');

            expect($settings)->toHaveKey('userMultiSort')
                ->and($settings['userMultiSort'])->toHaveCount(1);
        });

        it('loads userMultiSort from saved filter', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('saveFilter', 'Multi Sort Filter');

            $filterId = data_get($component->get('savedFilters'), '0.id');

            $fresh = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('loadedFilterId', $filterId)
                ->call('loadSavedFilter');

            expect($fresh->get('userMultiSort'))->toHaveCount(1)
                ->and($fresh->get('userMultiSort')[0]['column'])->toBe('created_at');
        });

        it('handles loading old filter without userMultiSort gracefully', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('saveFilter', 'Single Sort Filter');

            $filterId = data_get($component->get('savedFilters'), '0.id');

            $fresh = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('loadedFilterId', $filterId)
                ->call('loadSavedFilter');

            expect($fresh->get('userMultiSort'))->toBe([]);
        });
    });

    describe('clearFiltersAndSort', function (): void {
        it('clears userMultiSort when clearFiltersAndSort is called', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true)
                ->call('clearFiltersAndSort');

            expect($component->get('userMultiSort'))->toBe([]);
        });
    });

    describe('stale filter loading', function (): void {
        it('resets userMultiSort when loading old filter without userMultiSort key', function (): void {
            // First set up multi-sort
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true);

            expect($component->get('userMultiSort'))->toHaveCount(1);

            // Save a filter WITHOUT multi-sort (simulate old format)
            $component->call('sortTable', 'id', false) // clear multi-sort
                ->call('saveFilter', 'Old Style Filter');

            // Re-apply multi-sort
            $component->call('sortTable', 'title', false)
                ->call('sortTable', 'created_at', true);

            expect($component->get('userMultiSort'))->toHaveCount(1);

            // Load the old filter - should clear multi-sort
            $filterId = data_get($component->get('savedFilters'), '0.id');
            $component->set('loadedFilterId', $filterId)
                ->call('loadSavedFilter');

            expect($component->get('userMultiSort'))->toBe([]);
        });
    });
});
