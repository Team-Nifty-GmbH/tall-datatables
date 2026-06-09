<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\DefaultColumnsPostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->otherUser = createTestUser();
    $this->actingAs($this->user);
});

describe('Global Default Columns', function (): void {
    describe('canSaveDefaultColumns gate', function (): void {
        it('returns false by default', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $reflection = new ReflectionMethod($component->instance(), 'canSaveDefaultColumns');
            expect($reflection->invoke($component->instance()))->toBeFalse();
        });

        it('does not render button when gate is false', function (): void {
            Livewire::test(PostDataTable::class)
                ->assertDontSee(__('Set as Default'));
        });

        it('renders button when gate is true', function (): void {
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->assertSee(__('Set as Default'));
        });

        it('renders Set as Default button with dialog invoking saveDefaultColumns with bool flag (Livewire v4)', function (): void {
            $html = Livewire::test(DefaultColumnsPostDataTable::class)->html();

            expect($html)->toContain('$wire.saveDefaultColumns(true)')
                ->and($html)->toContain('$wire.saveDefaultColumns(false)')
                ->and($html)->not->toContain('wire:click="saveDefaultColumns"');
        });

        it('renders Reset Layout button with $wire-prefixed method call (Livewire v4)', function (): void {
            $html = Livewire::test(DefaultColumnsPostDataTable::class)->html();

            expect($html)->toContain('x-on:click="$wire.resetLayout()"')
                ->and($html)->not->toContain('x-on:click="resetLayout"');
        });

        it('silently no-ops saveDefaultColumns when gate is false', function (): void {
            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('saveDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(0);
        });
    });

    describe('saveDefaultColumns', function (): void {
        it('saves current enabledCols as global default', function (): void {
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns');

            $setting = DatatableUserSetting::where('is_default_columns', true)->first();
            expect($setting)->not->toBeNull()
                ->and($setting->settings['enabledCols'])->toBe(['id', 'title']);
        });

        it('only keeps one default per component', function (): void {
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns')
                ->set('enabledCols', ['id', 'title', 'created_at'])
                ->call('saveDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(1);
            $setting = DatatableUserSetting::where('is_default_columns', true)->first();
            expect($setting->settings['enabledCols'])->toBe(['id', 'title', 'created_at']);
        });

        describe('with resetOtherUserLayouts flag', function (): void {
            it('deletes all is_layout rows for the same component when true', function (): void {
                DatatableUserSetting::create([
                    'name' => 'OtherUserLayout',
                    'component' => DefaultColumnsPostDataTable::class,
                    'cache_key' => DefaultColumnsPostDataTable::class,
                    'settings' => ['enabledCols' => ['id']],
                    'is_layout' => true,
                    'is_default_columns' => false,
                    'is_permanent' => false,
                    'authenticatable_id' => $this->otherUser->getKey(),
                    'authenticatable_type' => get_class($this->otherUser),
                ]);

                Livewire::test(DefaultColumnsPostDataTable::class)
                    ->call('loadData')
                    ->set('enabledCols', ['id', 'title'])
                    ->call('saveDefaultColumns', true);

                expect(DatatableUserSetting::where('component', DefaultColumnsPostDataTable::class)->where('is_layout', true)->count())->toBe(0)
                    ->and(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(1);
            });

            it('keeps is_layout rows when flag is false (default)', function (): void {
                DatatableUserSetting::create([
                    'name' => 'OtherUserLayout',
                    'component' => DefaultColumnsPostDataTable::class,
                    'cache_key' => DefaultColumnsPostDataTable::class,
                    'settings' => ['enabledCols' => ['id']],
                    'is_layout' => true,
                    'is_default_columns' => false,
                    'is_permanent' => false,
                    'authenticatable_id' => $this->otherUser->getKey(),
                    'authenticatable_type' => get_class($this->otherUser),
                ]);

                Livewire::test(DefaultColumnsPostDataTable::class)
                    ->call('loadData')
                    ->set('enabledCols', ['id', 'title'])
                    ->call('saveDefaultColumns');

                expect(DatatableUserSetting::where('component', DefaultColumnsPostDataTable::class)->where('is_layout', true)->count())->toBe(1);
            });

            it('does not delete saved filter rows (is_layout=false) when flag is true', function (): void {
                DatatableUserSetting::create([
                    'name' => 'My filter',
                    'component' => DefaultColumnsPostDataTable::class,
                    'cache_key' => DefaultColumnsPostDataTable::class,
                    'settings' => ['userFilters' => []],
                    'is_layout' => false,
                    'is_default_columns' => false,
                    'is_permanent' => false,
                    'authenticatable_id' => $this->otherUser->getKey(),
                    'authenticatable_type' => get_class($this->otherUser),
                ]);

                Livewire::test(DefaultColumnsPostDataTable::class)
                    ->call('loadData')
                    ->set('enabledCols', ['id', 'title'])
                    ->call('saveDefaultColumns', true);

                expect(DatatableUserSetting::where('name', 'My filter')->count())->toBe(1);
            });

            it('does not touch layouts of unrelated components when flag is true', function (): void {
                DatatableUserSetting::create([
                    'name' => 'UnrelatedLayout',
                    'component' => PostDataTable::class,
                    'cache_key' => PostDataTable::class,
                    'settings' => ['enabledCols' => ['id']],
                    'is_layout' => true,
                    'is_default_columns' => false,
                    'is_permanent' => false,
                    'authenticatable_id' => $this->otherUser->getKey(),
                    'authenticatable_type' => get_class($this->otherUser),
                ]);

                Livewire::test(DefaultColumnsPostDataTable::class)
                    ->call('loadData')
                    ->set('enabledCols', ['id', 'title'])
                    ->call('saveDefaultColumns', true);

                expect(DatatableUserSetting::where('component', PostDataTable::class)->where('is_layout', true)->count())->toBe(1);
            });

            it('silently no-ops when gate is false even with reset flag', function (): void {
                DatatableUserSetting::create([
                    'name' => 'OtherUserLayout',
                    'component' => PostDataTable::class,
                    'cache_key' => PostDataTable::class,
                    'settings' => ['enabledCols' => ['id']],
                    'is_layout' => true,
                    'is_default_columns' => false,
                    'is_permanent' => false,
                    'authenticatable_id' => $this->otherUser->getKey(),
                    'authenticatable_type' => get_class($this->otherUser),
                ]);

                Livewire::test(PostDataTable::class)
                    ->call('loadData')
                    ->call('saveDefaultColumns', true);

                expect(DatatableUserSetting::where('component', PostDataTable::class)->where('is_layout', true)->count())->toBe(1)
                    ->and(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(0);
            });
        });

        it('overwrites default set by another user', function (): void {
            // User A sets default
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns');

            // User B overwrites
            $this->actingAs($this->otherUser);
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'created_at'])
                ->call('saveDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(1);
            $setting = DatatableUserSetting::where('is_default_columns', true)->first();
            expect($setting->settings['enabledCols'])->toBe(['id', 'created_at']);
        });
    });

    describe('loading global defaults on mount', function (): void {
        it('loads global default columns for any user', function (): void {
            // User A sets default
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => DefaultColumnsPostDataTable::class,
                'cache_key' => DefaultColumnsPostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
            ]);

            // User B sees the default
            $component = Livewire::test(DefaultColumnsPostDataTable::class);
            expect($component->get('enabledCols'))->toBe(['id', 'title']);
        });

        it('permanent filter takes precedence over global default', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => DefaultColumnsPostDataTable::class,
                'cache_key' => DefaultColumnsPostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
            ]);

            DatatableUserSetting::create([
                'name' => 'Permanent',
                'component' => DefaultColumnsPostDataTable::class,
                'cache_key' => DefaultColumnsPostDataTable::class,
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
                'authenticatable_type' => get_class($this->user),
            ]);

            $component = Livewire::test(DefaultColumnsPostDataTable::class);
            expect($component->get('enabledCols'))->toContain('created_at');
        });
    });

    describe('resetLayout', function (): void {
        it('resets to global default columns', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => DefaultColumnsPostDataTable::class,
                'cache_key' => DefaultColumnsPostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->otherUser->getKey(),
                'authenticatable_type' => get_class($this->otherUser),
            ]);

            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title', 'created_at', 'updated_at'])
                ->call('resetLayout')
                ->assertSet('enabledCols', ['id', 'title']);
        });

        it('resets to component defaults when no global default exists', function (): void {
            $component = Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData');

            $originalCols = $component->get('enabledCols');

            $component->set('enabledCols', ['id'])
                ->call('resetLayout');

            expect($component->get('enabledCols'))->toBe($originalCols);
        });
    });

    describe('deleteDefaultColumns', function (): void {
        it('deletes the global default', function (): void {
            Livewire::test(DefaultColumnsPostDataTable::class)
                ->call('loadData')
                ->set('enabledCols', ['id', 'title'])
                ->call('saveDefaultColumns')
                ->call('deleteDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(0);
        });

        it('silently no-ops when gate is false', function (): void {
            DatatableUserSetting::create([
                'name' => '__default_columns__',
                'component' => PostDataTable::class,
                'cache_key' => PostDataTable::class,
                'settings' => ['enabledCols' => ['id', 'title']],
                'is_default_columns' => true,
                'is_permanent' => false,
                'is_layout' => false,
                'authenticatable_id' => $this->user->getKey(),
                'authenticatable_type' => get_class($this->user),
            ]);

            Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->call('deleteDefaultColumns');

            expect(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(1);
        });
    });
});
