<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('User-controlled Default Columns', function (): void {
    describe('saveDefaultColumns', function (): void {
        it('saves current enabledCols as default columns setting', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns');

            $setting = DatatableUserSetting::query()
                ->where('authenticatable_id', $this->user->getKey())
                ->where('is_default_columns', true)
                ->first();

            expect($setting)->not->toBeNull()
                ->and($setting->settings['enabledCols'])->toBe(['id', 'title'])
                ->and($setting->name)->toBe('__default_columns__')
                ->and($setting->is_layout)->toBeFalse()
                ->and($setting->is_permanent)->toBeFalse();
        });

        it('updates existing default columns setting instead of creating duplicate', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns')
                ->set('enabledCols', ['id', 'title', 'created_at'])
                ->call('saveDefaultColumns');

            $count = DatatableUserSetting::query()
                ->where('authenticatable_id', $this->user->getKey())
                ->where('is_default_columns', true)
                ->count();

            expect($count)->toBe(1);

            $setting = DatatableUserSetting::query()
                ->where('authenticatable_id', $this->user->getKey())
                ->where('is_default_columns', true)
                ->first();

            expect($setting->settings['enabledCols'])->toBe(['id', 'title', 'created_at']);
        });

        it('stores component and cache_key correctly', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['title'])
                ->call('saveDefaultColumns');

            $setting = DatatableUserSetting::query()
                ->where('is_default_columns', true)
                ->first();

            expect($setting->component)->toBe(PostDataTable::class)
                ->and($setting->cache_key)->toBe(PostDataTable::class);
        });
    });

    describe('loading default columns on mount', function (): void {
        it('loads user default columns on mount when no permanent filter exists', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            $component = Livewire::test(PostDataTable::class);

            expect($component->get('enabledCols'))->toBe(['id', 'title']);
        });

        it('permanent filter takes precedence over default columns', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            DatatableUserSetting::create([
                'name' => 'Permanent',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => [
                    'enabledCols' => ['id', 'title', 'created_at'],
                    'userFilters' => [],
                    'userOrderBy' => '',
                    'userOrderAsc' => true,
                    'perPage' => 15,
                    'aggregatableCols' => ['sum' => [], 'avg' => [], 'min' => [], 'max' => []],
                ],
                'is_permanent' => true,
                'is_layout' => false,
                'is_default_columns' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            $component = Livewire::test(PostDataTable::class);

            expect($component->get('enabledCols'))->toContain('created_at');
        });

        it('does not load default columns when permanent filter exists', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            DatatableUserSetting::create([
                'name' => 'Permanent',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => [
                    'enabledCols' => ['title', 'content', 'price', 'is_published', 'created_at'],
                    'userFilters' => [],
                    'userOrderBy' => '',
                    'userOrderAsc' => true,
                    'perPage' => 15,
                ],
                'is_permanent' => true,
                'is_layout' => false,
                'is_default_columns' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            $component = Livewire::test(PostDataTable::class);

            // Should have the permanent filter's cols, not just ['title']
            expect($component->get('enabledCols'))->toBe(['title', 'content', 'price', 'is_published', 'created_at']);
        });
    });

    describe('resetLayout with default columns', function (): void {
        it('resets to user default columns when they exist', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => $this->user->getMorphClass(),
            ]);

            // Create a layout setting so resetLayout doesn't early-return
            $this->user->datatableUserSettings()->create([
                'name' => 'layout',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['title', 'content', 'created_at', 'is_published']],
                'is_layout' => true,
            ]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('resetLayout');

            expect($component->get('enabledCols'))->toBe(['id', 'title']);
        });

        it('resets to component defaults when no user default exists', function (): void {
            // Create a layout setting so resetLayout doesn't early-return
            $this->user->datatableUserSettings()->create([
                'name' => 'layout',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['id']],
                'is_layout' => true,
            ]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('resetLayout');

            // PostDataTable default enabledCols
            expect($component->get('enabledCols'))->toBe([
                'title',
                'content',
                'price',
                'is_published',
                'created_at',
            ]);
        });
    });

    describe('deleteDefaultColumns', function (): void {
        it('deletes the default columns setting', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns')
                ->call('deleteDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(0);
        });

        it('does not affect other settings when deleting default columns', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns')
                ->call('saveFilter', 'My Filter');

            $component->call('deleteDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(0);
            expect(DatatableUserSetting::where('name', 'My Filter')->exists())->toBeTrue();
        });
    });
});
