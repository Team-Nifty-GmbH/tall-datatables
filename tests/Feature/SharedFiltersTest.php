<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\SharedFiltersPostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->otherUser = createTestUser();
    $this->actingAs($this->user);
});

describe('Shared Saved Filters', function (): void {
    describe('canShareFilters gate', function (): void {
        it('returns false by default', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $reflection = new ReflectionMethod($component->instance(), 'canShareFilters');
            expect($reflection->invoke($component->instance()))->toBeFalse();
        });

        it('does not save is_shared when gate is false', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('saveFilter', 'Test', false, true, true);

            $setting = DatatableUserSetting::where('name', 'Test')->first();
            expect($setting->is_shared)->toBeFalse();
        });
    });

    describe('saving shared filters', function (): void {
        it('saves filter as shared when gate is true', function (): void {
            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('saveFilter', 'Shared Filter', false, true, true);

            $setting = DatatableUserSetting::where('name', 'Shared Filter')->first();
            expect($setting->is_shared)->toBeTrue();
        });

        it('saves filter as non-shared by default', function (): void {
            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('saveFilter', 'Private Filter');

            $setting = DatatableUserSetting::where('name', 'Private Filter')->first();
            expect($setting->is_shared)->toBeFalse();
        });
    });

    describe('loading shared filters', function (): void {
        it('includes shared filters from other users when gate is true', function (): void {
            DatatableUserSetting::create([
                'name' => 'Other User Filter',
                'component' => SharedFiltersPostDataTable::class,
                'cache_key' => SharedFiltersPostDataTable::class,
                'settings' => [
                    'userFilters' => [],
                    'userOrderBy' => '',
                    'userOrderAsc' => true,
                    'perPage' => 15,
                    'aggregatableCols' => ['sum' => [], 'avg' => [], 'min' => [], 'max' => []],
                ],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => true,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            $component = Livewire::test(SharedFiltersPostDataTable::class);
            $names = collect($component->get('savedFilters'))->pluck('name')->toArray();
            expect($names)->toContain('Other User Filter');
        });

        it('does not include non-shared filters from other users', function (): void {
            DatatableUserSetting::create([
                'name' => 'Private Other',
                'component' => SharedFiltersPostDataTable::class,
                'cache_key' => SharedFiltersPostDataTable::class,
                'settings' => ['userFilters' => []],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => false,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            $component = Livewire::test(SharedFiltersPostDataTable::class);
            $names = collect($component->get('savedFilters'))->pluck('name')->toArray();
            expect($names)->not->toContain('Private Other');
        });

        it('does not include shared filters when gate is false', function (): void {
            DatatableUserSetting::create([
                'name' => 'Shared By Other',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['userFilters' => []],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => true,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            $component = Livewire::test(PostDataTable::class);
            $names = collect($component->get('savedFilters'))->pluck('name')->toArray();
            expect($names)->not->toContain('Shared By Other');
        });
    });

    describe('authorization', function (): void {
        it('cannot delete shared filter owned by another user', function (): void {
            $setting = DatatableUserSetting::create([
                'name' => 'Others Shared',
                'component' => SharedFiltersPostDataTable::class,
                'cache_key' => SharedFiltersPostDataTable::class,
                'settings' => ['userFilters' => []],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => true,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('deleteSavedFilter', $setting->getKey());

            expect(DatatableUserSetting::find($setting->getKey()))->not->toBeNull();
        });

        it('can delete own shared filter', function (): void {
            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('saveFilter', 'My Shared', false, true, true);

            $setting = DatatableUserSetting::where('name', 'My Shared')->first();

            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('deleteSavedFilter', $setting->getKey());

            expect(DatatableUserSetting::find($setting->getKey()))->toBeNull();
        });

        it('cannot update name of shared filter owned by another user', function (): void {
            $setting = DatatableUserSetting::create([
                'name' => 'Others Filter',
                'component' => SharedFiltersPostDataTable::class,
                'cache_key' => SharedFiltersPostDataTable::class,
                'settings' => ['userFilters' => []],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => true,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('updateSavedFilter', $setting->getKey(), ['name' => 'Renamed']);

            expect(DatatableUserSetting::find($setting->getKey())->name)->toBe('Others Filter');
        });
    });

    describe('toggling shared status', function (): void {
        it('can toggle is_shared on own filter', function (): void {
            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('saveFilter', 'My Filter');

            $setting = DatatableUserSetting::where('name', 'My Filter')->first();

            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('updateSavedFilter', $setting->getKey(), ['is_shared' => true]);

            expect(DatatableUserSetting::find($setting->getKey())->is_shared)->toBeTrue();
        });
    });

    describe('is_own flag', function (): void {
        it('includes is_own flag in saved filter data', function (): void {
            Livewire::test(SharedFiltersPostDataTable::class)
                ->call('loadData')
                ->call('saveFilter', 'My Filter');

            $component = Livewire::test(SharedFiltersPostDataTable::class);
            $filter = collect($component->get('savedFilters'))->first();

            expect($filter)->toHaveKey('is_own')
                ->and($filter['is_own'])->toBeTrue();
        });

        it('marks shared filters from other users as not own', function (): void {
            DatatableUserSetting::create([
                'name' => 'Others Filter',
                'component' => SharedFiltersPostDataTable::class,
                'cache_key' => SharedFiltersPostDataTable::class,
                'settings' => ['userFilters' => [], 'userOrderBy' => '', 'userOrderAsc' => true, 'perPage' => 15, 'aggregatableCols' => ['sum' => [], 'avg' => [], 'min' => [], 'max' => []]],
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
                'is_shared' => true,
                'is_permanent' => false,
                'is_layout' => false,
            ]);

            $component = Livewire::test(SharedFiltersPostDataTable::class);
            $otherFilter = collect($component->get('savedFilters'))
                ->where('name', 'Others Filter')
                ->first();

            expect($otherFilter['is_own'])->toBeFalse();
        });
    });

    describe('view data', function (): void {
        it('exposes canShareFilters as false by default', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $reflection = new ReflectionMethod($component->instance(), 'getViewData');
            $viewData = $reflection->invoke($component->instance());
            expect($viewData['canShareFilters'])->toBeFalse();
        });

        it('exposes canShareFilters as true when gate is enabled', function (): void {
            $component = Livewire::test(SharedFiltersPostDataTable::class);
            $reflection = new ReflectionMethod($component->instance(), 'getViewData');
            $viewData = $reflection->invoke($component->instance());
            expect($viewData['canShareFilters'])->toBeTrue();
        });
    });
});
