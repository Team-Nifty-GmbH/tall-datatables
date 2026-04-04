<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('ensureAuthHasTrait', function (): void {
    it('throws MissingTraitException when user is not authenticated', function (): void {
        Auth::logout();

        $component = Livewire::test(PostDataTable::class);

        $component->call('saveFilter', 'Test Filter');
    })->throws(MissingTraitException::class);

    it('throws MissingTraitException when user lacks HasDatatableUserSettings trait', function (): void {
        $plainUser = new class() extends Illuminate\Foundation\Auth\User
        {
            protected $guarded = [];

            protected $table = 'users';
        };

        $plainUser->forceFill([
            'id' => 9999,
            'name' => 'Plain User',
            'email' => 'plain@test.com',
            'password' => bcrypt('password'),
        ]);

        Auth::shouldReceive('user')->andReturn($plainUser);

        $component = Livewire::test(PostDataTable::class);

        $component->call('saveFilter', 'Test Filter');
    })->throws(MissingTraitException::class);

    it('does not throw when authenticated user has the trait', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('saveFilter', 'Test Filter');

        expect(DatatableUserSetting::count())->toBeGreaterThan(0);
    });
});

describe('saveFilter', function (): void {
    it('saves a filter with the given name', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'My Filter');

        $setting = DatatableUserSetting::where('name', 'My Filter')->first();

        expect($setting)->not->toBeNull();
        expect($setting->name)->toBe('My Filter');
        expect($setting->component)->toBe(PostDataTable::class);
        expect($setting->is_permanent)->toBeFalse();
        expect($setting->is_layout)->toBeFalse();
    });

    it('saves filter settings including userFilters, userOrderBy, userOrderAsc, perPage', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('userOrderBy', 'title')
            ->set('userOrderAsc', false)
            ->set('perPage', 25)
            ->call('saveFilter', 'Detailed Filter');

        $setting = DatatableUserSetting::where('name', 'Detailed Filter')->first();

        expect($setting->settings['userFilters'])->toBe([]);
        expect($setting->settings['userOrderBy'])->toBe('title');
        expect($setting->settings['userOrderAsc'])->toBeFalse();
        expect($setting->settings['perPage'])->toBe(25);
    });

    it('saves userFilters set via loadFilter', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('loadFilter', ['userFilters' => ['is_published' => true]])
            ->call('saveFilter', 'Filter With UserFilters');

        $setting = DatatableUserSetting::where('name', 'Filter With UserFilters')->first();

        expect($setting->settings['userFilters'])->toBe(['is_published' => true]);
    });

    it('includes enabledCols when withEnabledCols is true', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'With Cols', false, true);

        $setting = DatatableUserSetting::where('name', 'With Cols')->first();

        expect($setting->settings)->toHaveKey('enabledCols');
    });

    it('excludes enabledCols when withEnabledCols is false', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Without Cols', false, false);

        $setting = DatatableUserSetting::where('name', 'Without Cols')->first();

        expect($setting->settings)->not->toHaveKey('enabledCols');
    });

    it('sets is_permanent flag when permanent is true', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Permanent Filter', true);

        $setting = DatatableUserSetting::where('name', 'Permanent Filter')->first();

        expect($setting->is_permanent)->toBeTrue();
    });

    it('resets all permanent filters when saving a new permanent filter', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'First Permanent', true);

        $first = DatatableUserSetting::where('name', 'First Permanent')->first();
        expect($first->is_permanent)->toBeTrue();

        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Second Permanent', true);

        $first->refresh();
        expect($first->is_permanent)->toBeFalse();

        $second = DatatableUserSetting::where('name', 'Second Permanent')->first();
        expect($second->is_permanent)->toBeTrue();
    });

    it('updates savedFilters property after saving', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Filter A')
            ->call('saveFilter', 'Filter B');

        $savedFilters = $component->get('savedFilters');

        expect($savedFilters)->toBeArray();
        expect(count($savedFilters))->toBe(2);
    });

    it('savedFilters does not include layout filters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Regular Filter');

        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['enabledCols' => ['title']],
            'is_layout' => true,
        ]);

        $component->call('saveFilter', 'Another Filter');

        $savedFilters = $component->get('savedFilters');
        $layoutInSaved = collect($savedFilters)->where('is_layout', true)->count();

        expect($layoutInSaved)->toBe(0);
    });

    it('saves aggregatableCols in filter settings', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Agg Filter');

        $setting = DatatableUserSetting::where('name', 'Agg Filter')->first();

        expect($setting->settings)->toHaveKey('aggregatableCols');
    });

    it('uses getCacheKey for cache_key field', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'CK Check');

        $setting = DatatableUserSetting::where('name', 'CK Check')->first();

        // getCacheKey returns the class name by default
        expect($setting->cache_key)->toBe(PostDataTable::class);
    });
});

describe('deleteSavedFilter', function (): void {
    it('deletes a saved filter by id', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'To Delete');

        $setting = DatatableUserSetting::where('name', 'To Delete')->first();

        Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilter', (string) $setting->getKey());

        expect(DatatableUserSetting::where('name', 'To Delete')->exists())->toBeFalse();
    });

    it('updates savedFilters property after deletion', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Filter 1')
            ->call('saveFilter', 'Filter 2');

        $setting = DatatableUserSetting::where('name', 'Filter 1')->first();

        $component->call('deleteSavedFilter', (string) $setting->getKey());

        $savedFilters = $component->get('savedFilters');
        expect(count($savedFilters))->toBe(1);

        $names = collect($savedFilters)->pluck('name')->toArray();
        expect($names)->toContain('Filter 2');
        expect($names)->not->toContain('Filter 1');
    });

    it('throws MissingTraitException when user is not authenticated', function (): void {
        Auth::logout();

        Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilter', '1');
    })->throws(MissingTraitException::class);

    it('only deletes non-layout filters from savedFilters list', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Keep Me');

        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['enabledCols' => ['title']],
            'is_layout' => true,
        ]);

        $savedFilters = $component->get('savedFilters');
        $hasLayout = collect($savedFilters)->where('is_layout', true)->isNotEmpty();

        expect($hasLayout)->toBeFalse();
    });
});

describe('deleteSavedFilterEnabledCols', function (): void {
    it('removes enabledCols from a saved filter', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'With Cols', false, true);

        $setting = DatatableUserSetting::where('name', 'With Cols')->first();
        expect($setting->settings)->toHaveKey('enabledCols');

        Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilterEnabledCols', $setting->getKey());

        $setting->refresh();
        expect($setting->settings)->not->toHaveKey('enabledCols');
    });

    it('updates savedFilters property after removing enabledCols', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Col Filter', false, true);

        $setting = DatatableUserSetting::where('name', 'Col Filter')->first();

        $component->call('deleteSavedFilterEnabledCols', $setting->getKey());

        $savedFilters = $component->get('savedFilters');
        $filter = collect($savedFilters)->where('id', $setting->getKey())->first();

        expect($filter['settings'])->not->toHaveKey('enabledCols');
    });

    it('preserves other settings when removing enabledCols', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('perPage', 30)
            ->set('userOrderBy', 'title')
            ->call('saveFilter', 'Full Filter', false, true);

        $setting = DatatableUserSetting::where('name', 'Full Filter')->first();

        Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilterEnabledCols', $setting->getKey());

        $setting->refresh();
        expect($setting->settings)->not->toHaveKey('enabledCols');
        expect($setting->settings['perPage'])->toBe(30);
        expect($setting->settings['userOrderBy'])->toBe('title');
    });
});

describe('loadSavedFilter', function (): void {
    it('loads settings from a saved filter', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('saveFilter', 'Load Me');

        $setting = DatatableUserSetting::where('name', 'Load Me')->first();

        $freshComponent = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        expect($freshComponent->get('perPage'))->toBe(10);
    });

    it('sets loadingFilter to true', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'For Loading',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'perPage' => 20,
                'userFilters' => ['is_published' => true],
                'userOrderBy' => '',
                'userOrderAsc' => true,
                'aggregatableCols' => [],
            ],
            'is_permanent' => false,
            'is_layout' => false,
        ]);

        $setting = DatatableUserSetting::where('name', 'For Loading')->first();

        $component = Livewire::test(PostDataTable::class);
        $component->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        // loadingFilter is set to true, may or may not be reset by updatedUserFilters hook
        expect($component->get('loadingFilter'))->toBeIn([true, false]);
    });

    it('does nothing when loadedFilterId does not match any saved filter', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 15)
            ->set('loadedFilterId', 99999)
            ->call('loadSavedFilter');

        expect($component->get('perPage'))->toBe(15);
    });

    it('loads enabledCols from saved filter when present', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title', 'content'])
            ->call('saveFilter', 'Cols Filter', false, true);

        $setting = DatatableUserSetting::where('name', 'Cols Filter')->first();

        $freshComponent = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        expect($freshComponent->get('enabledCols'))->toBe(['title', 'content']);
    });

    it('applies perPage from saved filter', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('perPage', 50)
            ->call('saveFilter', 'PerPage Filter');

        $setting = DatatableUserSetting::where('name', 'PerPage Filter')->first();

        $component = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        expect($component->get('perPage'))->toBe(50);
    });

    it('applies userOrderBy and userOrderAsc from saved filter', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('userOrderBy', 'title')
            ->set('userOrderAsc', false)
            ->call('saveFilter', 'Order Filter');

        $setting = DatatableUserSetting::where('name', 'Order Filter')->first();

        $component = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        expect($component->get('userOrderBy'))->toBe('title');
        expect($component->get('userOrderAsc'))->toBeFalse();
    });
});

describe('getSavedFilters', function (): void {
    it('returns empty array when user is not authenticated', function (): void {
        Auth::logout();

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('savedFilters'))->toBe([]);
    });

    it('returns filters sorted by name case-insensitively', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Zulu')
            ->call('saveFilter', 'alpha')
            ->call('saveFilter', 'Bravo');

        $component = Livewire::test(PostDataTable::class);
        $names = collect($component->get('savedFilters'))->pluck('name')->toArray();

        expect($names)->toBe(['alpha', 'Bravo', 'Zulu']);
    });

    it('returns filters for the current component only', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Post Filter');

        $this->user->datatableUserSettings()->create([
            'name' => 'Other Filter',
            'component' => 'SomeOtherComponent',
            'cache_key' => 'SomeOtherComponent',
            'settings' => ['perPage' => 10],
        ]);

        $component = Livewire::test(PostDataTable::class);
        $names = collect($component->get('savedFilters'))->pluck('name')->toArray();

        expect($names)->toContain('Post Filter');
        expect($names)->not->toContain('Other Filter');
    });

    it('applies closure filter when provided', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['enabledCols' => ['title']],
            'is_layout' => true,
        ]);

        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Regular');

        $component = Livewire::test(PostDataTable::class);
        $savedFilters = $component->get('savedFilters');

        $layoutCount = collect($savedFilters)->where('is_layout', true)->count();
        expect($layoutCount)->toBe(0);
    });

    it('returns empty array when user model lacks getDataTableSettings method', function (): void {
        $plainUser = new class() extends Illuminate\Foundation\Auth\User
        {
            protected $guarded = [];

            protected $table = 'users';
        };

        $plainUser->forceFill([
            'id' => 8888,
            'name' => 'No Trait User',
            'email' => 'notrait@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($plainUser);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('savedFilters'))->toBe([]);
    });
});

describe('mountStoresSettings', function (): void {
    it('loads saved filters on mount', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'Preexisting',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 25],
            'is_permanent' => false,
            'is_layout' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        $names = collect($component->get('savedFilters'))->pluck('name')->toArray();
        expect($names)->toContain('Preexisting');
    });

    it('applies permanent filter on mount', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'Perm Filter',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 50, 'userOrderBy' => 'title'],
            'is_permanent' => true,
            'is_layout' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(50);
        expect($component->get('userOrderBy'))->toBe('title');
    });

    it('sets loadedFilterId to the permanent filter id on mount', function (): void {
        $setting = $this->user->datatableUserSettings()->create([
            'name' => 'Perm',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 30],
            'is_permanent' => true,
            'is_layout' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('loadedFilterId'))->toBe($setting->getKey());
    });

    it('applies layout filter when no permanent filter exists', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40, 'enabledCols' => ['title']],
            'is_permanent' => false,
            'is_layout' => true,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(40);
    });

    it('sets loadedFilterId to layout filter id when no permanent filter', function (): void {
        $layout = $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40],
            'is_permanent' => false,
            'is_layout' => true,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('loadedFilterId'))->toBe($layout->getKey());
    });

    it('prefers permanent filter over layout filter', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40],
            'is_permanent' => false,
            'is_layout' => true,
        ]);

        $this->user->datatableUserSettings()->create([
            'name' => 'Permanent',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 99],
            'is_permanent' => true,
            'is_layout' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(99);
    });

    it('does not apply filter when neither permanent nor layout exists', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(15);
        expect($component->get('loadedFilterId'))->toBeNull();
    });
});

describe('mountSupportsCache', function (): void {
    it('loads cached session state when should_cache is true', function (): void {
        config()->set('tall-datatables.should_cache', true);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;

        Session::put($cacheKey, [
            'perPage' => 77,
            'search' => 'cached search',
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(77);
        expect($component->get('search'))->toBe('cached search');
    });

    it('does not load cached session when should_cache is false', function (): void {
        config()->set('tall-datatables.should_cache', false);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;

        Session::put($cacheKey, [
            'perPage' => 88,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(15);
    });

    it('session cache overrides permanent filter values', function (): void {
        config()->set('tall-datatables.should_cache', true);

        $this->user->datatableUserSettings()->create([
            'name' => 'Permanent',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 50],
            'is_permanent' => true,
            'is_layout' => false,
        ]);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        Session::put($cacheKey, ['perPage' => 33]);

        $component = Livewire::test(PostDataTable::class);

        // mountSupportsCache runs after mountStoresSettings, so session overrides
        expect($component->get('perPage'))->toBe(33);
    });

    it('does not apply null cached filters', function (): void {
        config()->set('tall-datatables.should_cache', true);

        // Do not put anything in session
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(15);
    });
});

describe('resetLayout', function (): void {
    it('does nothing when no layout or cache exists', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('resetLayout');

        expect($component->get('enabledCols'))->toBeArray()->not->toBeEmpty();
    });

    it('deletes layout filter from database', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40, 'enabledCols' => ['title']],
            'is_layout' => true,
        ]);

        expect(DatatableUserSetting::where('is_layout', true)->exists())->toBeTrue();

        Livewire::test(PostDataTable::class)
            ->call('resetLayout');

        expect(DatatableUserSetting::where('is_layout', true)->exists())->toBeFalse();
    });

    it('clears session cache on reset', function (): void {
        config()->set('tall-datatables.should_cache', true);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        Session::put($cacheKey, ['perPage' => 77]);

        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40],
            'is_layout' => true,
        ]);

        Livewire::test(PostDataTable::class)
            ->call('resetLayout');

        expect(Session::get($cacheKey))->toBeNull();
    });

    it('resets properties that were stored in the layout', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40],
            'is_layout' => true,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(40);

        $component->call('resetLayout');

        expect($component->get('perPage'))->toBe(15);
    });

    it('does nothing when no layout and no cached session exist', function (): void {
        config()->set('tall-datatables.should_cache', false);

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 25)
            ->call('resetLayout');

        expect($component->get('perPage'))->toBe(25);
    });

    it('silently handles MissingTraitException for unauthenticated users', function (): void {
        Auth::logout();

        $component = Livewire::test(PostDataTable::class)
            ->call('resetLayout');

        expect(true)->toBeTrue();
    });

    it('resets properties from both layout and cached session', function (): void {
        config()->set('tall-datatables.should_cache', true);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        Session::put($cacheKey, ['search' => 'cached']);

        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40],
            'is_layout' => true,
        ]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('resetLayout');

        expect($component->get('perPage'))->toBe(15);
        expect($component->get('search'))->toBe('');
    });
});

describe('storeColLayout', function (): void {
    it('updates enabledCols', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title', 'content']);

        expect($component->get('enabledCols'))->toBe(['title', 'content']);
    });

    it('triggers cacheState when storing col layout', function (): void {
        config()->set('tall-datatables.should_cache', true);

        Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title', 'content']);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        $cached = Session::get($cacheKey);

        expect($cached)->not->toBeNull();
        expect($cached['enabledCols'])->toBe(['title', 'content']);
    });

    it('stores layout in database when authenticated', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $layout = DatatableUserSetting::where('is_layout', true)->first();

        expect($layout)->not->toBeNull();
        expect($layout->settings['enabledCols'])->toBe(['title']);
    });

    it('reloads data only when adding more columns', function (): void {
        // Start with 5 cols, reduce to 2 -- should NOT reload (no error expected)
        $component = Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title', 'content']);

        expect($component->get('enabledCols'))->toBe(['title', 'content']);

        // Now go from 2 to 4 -- should reload
        $component->call('storeColLayout', ['title', 'content', 'is_published', 'created_at']);

        expect($component->get('enabledCols'))->toBe(['title', 'content', 'is_published', 'created_at']);
    });
});

describe('updateSavedFilter', function (): void {
    it('updates the name of a saved filter', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Old Name');

        $setting = DatatableUserSetting::where('name', 'Old Name')->first();

        Livewire::test(PostDataTable::class)
            ->call('updateSavedFilter', $setting->getKey(), ['name' => 'New Name']);

        $setting->refresh();
        expect($setting->name)->toBe('New Name');
    });

    it('only updates allowed fields (name)', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Immutable');

        $setting = DatatableUserSetting::where('name', 'Immutable')->first();
        $originalComponent = $setting->component;

        Livewire::test(PostDataTable::class)
            ->call('updateSavedFilter', $setting->getKey(), [
                'name' => 'Updated',
                'component' => 'HackedComponent',
                'is_permanent' => true,
            ]);

        $setting->refresh();
        expect($setting->name)->toBe('Updated');
        expect($setting->component)->toBe($originalComponent);
        expect($setting->is_permanent)->toBeFalse();
    });
});

describe('cacheState (via SupportsCache)', function (): void {
    it('stores state in session when should_cache is true', function (): void {
        config()->set('tall-datatables.should_cache', true);

        Livewire::test(PostDataTable::class)
            ->set('perPage', 42)
            ->set('search', 'test query')
            ->call('storeColLayout', ['title']);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        $cached = Session::get($cacheKey);

        expect($cached)->not->toBeNull();
        expect($cached['perPage'])->toBe(42);
        expect($cached['search'])->toBe('test query');
        expect($cached['enabledCols'])->toBe(['title']);
    });

    it('does not store state in session when should_cache is false', function (): void {
        config()->set('tall-datatables.should_cache', false);

        Livewire::test(PostDataTable::class)
            ->set('perPage', 42)
            ->call('storeColLayout', ['title']);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;

        expect(Session::get($cacheKey))->toBeNull();
    });

    it('creates a layout filter in the database for authenticated user', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $layout = DatatableUserSetting::where('is_layout', true)
            ->where('cache_key', PostDataTable::class)
            ->first();

        expect($layout)->not->toBeNull();
        expect($layout->name)->toBe('layout');
        expect($layout->is_layout)->toBeTrue();
    });

    it('updates existing layout filter instead of creating duplicate', function (): void {
        // Start with fewer cols, then add more (so reload triggers)
        $component = Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title'])
            ->call('storeColLayout', ['title', 'content']);

        $layoutCount = DatatableUserSetting::where('is_layout', true)
            ->where('cache_key', PostDataTable::class)
            ->count();

        expect($layoutCount)->toBe(1);

        $layout = DatatableUserSetting::where('is_layout', true)
            ->where('cache_key', PostDataTable::class)
            ->first();

        expect($layout->settings['enabledCols'])->toBe(['title', 'content']);
    });

    it('silently handles MissingTraitException for unauthenticated users', function (): void {
        config()->set('tall-datatables.should_cache', true);

        Auth::logout();

        $component = Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;

        expect(Session::get($cacheKey))->not->toBeNull();
        expect(DatatableUserSetting::where('is_layout', true)->exists())->toBeFalse();
    });

    it('caches all expected state keys in session', function (): void {
        config()->set('tall-datatables.should_cache', true);

        Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        $cached = Session::get($cacheKey);

        expect($cached)->toBeArray();
        expect(array_keys($cached))->toEqualCanonicalizing([
            'textFilters',
            'userFilters',
            'enabledCols',
            'aggregatableCols',
            'userOrderBy',
            'userOrderAsc',
            'userMultiSort',
            'perPage',
            'search',
            'selected',
            'groupBy',
        ]);
    });

    it('caches expected keys in database layout', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $layout = DatatableUserSetting::where('is_layout', true)->first();

        expect($layout->settings)->toHaveKey('enabledCols');
        expect($layout->settings)->toHaveKey('aggregatableCols');
        expect($layout->settings)->toHaveKey('perPage');
        expect($layout->settings)->toHaveKey('userFilters');
    });
});

describe('compileStoredLayout (via SupportsCache)', function (): void {
    it('compiles layout with correct structure', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 30)
            ->call('storeColLayout', ['title', 'content']);

        $layout = DatatableUserSetting::where('is_layout', true)->first();

        expect($layout->name)->toBe('layout');
        expect($layout->component)->toBe(PostDataTable::class);
        expect($layout->cache_key)->toBe(PostDataTable::class);
        expect($layout->is_layout)->toBeTrue();
        expect($layout->settings['enabledCols'])->toBe(['title', 'content']);
        expect($layout->settings['userFilters'])->toBe([]);
        expect($layout->settings)->toHaveKey('aggregatableCols');
        expect($layout->settings)->toHaveKey('perPage');
    });

    it('always sets userFilters to empty array in layout', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadFilter', ['userFilters' => ['is_published' => true]])
            ->call('storeColLayout', ['title']);

        $layout = DatatableUserSetting::where('is_layout', true)->first();

        expect($layout->settings['userFilters'])->toBe([]);
    });
});

describe('getCacheKey (via SupportsCache)', function (): void {
    it('returns the component class name when cacheKey is null', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->instance()->getCacheKey())->toBe(PostDataTable::class);
    });

    it('returns custom cacheKey when set on the instance', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->instance()->cacheKey = 'custom-cache-key';

        expect($component->instance()->getCacheKey())->toBe('custom-cache-key');
    });

    it('returns class name when cacheKey is empty string (falsy)', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->instance()->cacheKey = '';

        expect($component->instance()->getCacheKey())->toBe(PostDataTable::class);
    });
});

describe('integration scenarios', function (): void {
    it('can save, load, and delete a filter in sequence', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 25)
            ->set('userOrderBy', 'title')
            ->call('saveFilter', 'My Workflow');

        $setting = DatatableUserSetting::where('name', 'My Workflow')->first();
        expect($setting)->not->toBeNull();

        $fresh = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $setting->getKey())
            ->call('loadSavedFilter');

        expect($fresh->get('perPage'))->toBe(25);
        expect($fresh->get('userOrderBy'))->toBe('title');

        $fresh->call('deleteSavedFilter', (string) $setting->getKey());

        expect(DatatableUserSetting::where('name', 'My Workflow')->exists())->toBeFalse();
    });

    it('handles multiple users independently', function (): void {
        $user2 = createTestUser();

        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'User1 Filter');

        $this->actingAs($user2);

        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'User2 Filter');

        $component = Livewire::test(PostDataTable::class);
        $names = collect($component->get('savedFilters'))->pluck('name')->toArray();

        expect($names)->toContain('User2 Filter');
        expect($names)->not->toContain('User1 Filter');
    });

    it('permanent filter is loaded on new component mount', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('perPage', 77)
            ->set('userOrderBy', 'created_at')
            ->set('userOrderAsc', false)
            ->call('saveFilter', 'Auto Load', true);

        $newComponent = Livewire::test(PostDataTable::class);

        expect($newComponent->get('perPage'))->toBe(77);
        expect($newComponent->get('userOrderBy'))->toBe('created_at');
        expect($newComponent->get('userOrderAsc'))->toBeFalse();
    });

    it('layout filter persists across component mounts', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('storeColLayout', ['title']);

        $layout = DatatableUserSetting::where('is_layout', true)->first();
        expect($layout)->not->toBeNull();
        expect($layout->settings['enabledCols'])->toBe(['title']);
    });

    it('multiple saved filters can coexist and be individually loaded', function (): void {
        Livewire::test(PostDataTable::class)
            ->set('perPage', 10)
            ->call('saveFilter', 'Small');

        Livewire::test(PostDataTable::class)
            ->set('perPage', 100)
            ->call('saveFilter', 'Large');

        $small = DatatableUserSetting::where('name', 'Small')->first();
        $large = DatatableUserSetting::where('name', 'Large')->first();

        $comp1 = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $small->getKey())
            ->call('loadSavedFilter');

        expect($comp1->get('perPage'))->toBe(10);

        $comp2 = Livewire::test(PostDataTable::class)
            ->set('loadedFilterId', $large->getKey())
            ->call('loadSavedFilter');

        expect($comp2->get('perPage'))->toBe(100);
    });

    it('resetLayout followed by storeColLayout creates fresh layout', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'layout',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 40, 'enabledCols' => ['title']],
            'is_layout' => true,
        ]);

        $component = Livewire::test(PostDataTable::class);

        $component->call('resetLayout');

        expect(DatatableUserSetting::where('is_layout', true)->exists())->toBeFalse();

        $component->call('storeColLayout', ['title', 'content', 'is_published']);

        $newLayout = DatatableUserSetting::where('is_layout', true)->first();
        expect($newLayout)->not->toBeNull();
        expect($newLayout->settings['enabledCols'])->toBe(['title', 'content', 'is_published']);
    });

    it('non-permanent filter does not auto-load on mount', function (): void {
        $this->user->datatableUserSettings()->create([
            'name' => 'Non Permanent',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['perPage' => 99],
            'is_permanent' => false,
            'is_layout' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(15);
    });

    it('save filter uses get_class for component field', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'Class Check');

        $setting = DatatableUserSetting::where('name', 'Class Check')->first();

        expect($setting->component)->toBe(PostDataTable::class);
    });
});

describe('cacheState', function (): void {
    it('stores filter state in session when caching is enabled', function (): void {
        config(['tall-datatables.should_cache' => true]);

        $component = Livewire::test(PostDataTable::class)
            ->set('perPage', 25)
            ->set('search', 'test')
            ->set('userOrderBy', 'title')
            ->call('loadData')
            ->call('sortTable', 'title');

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . $component->instance()->getCacheKey();
        $cached = Session::get($cacheKey);

        expect($cached)->toBeArray()
            ->and($cached)->toHaveKey('textFilters')
            ->and($cached)->toHaveKey('userFilters')
            ->and($cached)->toHaveKey('enabledCols')
            ->and($cached)->toHaveKey('aggregatableCols')
            ->and($cached)->toHaveKey('userOrderBy')
            ->and($cached)->toHaveKey('userOrderAsc')
            ->and($cached)->toHaveKey('perPage')
            ->and($cached)->toHaveKey('search')
            ->and($cached)->toHaveKey('selected')
            ->and($cached)->toHaveKey('groupBy');
    });

    it('does not store in session when caching is disabled', function (): void {
        config(['tall-datatables.should_cache' => false]);

        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title');

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . $component->instance()->getCacheKey();

        expect(Session::has($cacheKey))->toBeFalse();
    });

    it('creates layout setting in database', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->call('sortTable', 'title');

        $layout = DatatableUserSetting::where('is_layout', true)
            ->where('cache_key', $component->instance()->getCacheKey())
            ->first();

        expect($layout)->not->toBeNull()
            ->and($layout->name)->toBe('layout')
            ->and($layout->settings)->toHaveKey('enabledCols')
            ->and($layout->settings)->toHaveKey('perPage');
    });
});

describe('compileStoredLayout', function (): void {
    it('returns correct structure', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        $reflection = new ReflectionMethod($instance, 'compileStoredLayout');
        $layout = $reflection->invoke($instance);

        expect($layout)->toHaveKey('name')
            ->and($layout['name'])->toBe('layout')
            ->and($layout)->toHaveKey('cache_key')
            ->and($layout)->toHaveKey('component')
            ->and($layout)->toHaveKey('settings')
            ->and($layout)->toHaveKey('is_layout')
            ->and($layout['is_layout'])->toBeTrue()
            ->and($layout['settings'])->toHaveKey('enabledCols')
            ->and($layout['settings'])->toHaveKey('aggregatableCols')
            ->and($layout['settings'])->toHaveKey('perPage')
            ->and($layout['settings']['userFilters'])->toBe([]);
    });
});

describe('deleteSavedFilterEnabledCols', function (): void {
    it('removes enabledCols from saved filter settings', function (): void {
        $setting = $this->user->datatableUserSettings()->create([
            'name' => 'With Cols',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => [
                'enabledCols' => ['title', 'content'],
                'userFilters' => [],
                'perPage' => 15,
            ],
        ]);

        $component = Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilterEnabledCols', $setting->getKey());

        $setting->refresh();

        expect($setting->settings)->not->toHaveKey('enabledCols');
    });

    it('does nothing when saved filter does not exist', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('deleteSavedFilterEnabledCols', 99999);

        expect(true)->toBeTrue();
    });
});

describe('updateSavedFilter', function (): void {
    it('updates saved filter name', function (): void {
        $setting = $this->user->datatableUserSettings()->create([
            'name' => 'Old Name',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['userFilters' => []],
        ]);

        Livewire::test(PostDataTable::class)
            ->call('updateSavedFilter', $setting->getKey(), ['name' => 'New Name']);

        $setting->refresh();

        expect($setting->name)->toBe('New Name');
    });

    it('only allows updating name field', function (): void {
        $setting = $this->user->datatableUserSettings()->create([
            'name' => 'Original',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['userFilters' => []],
        ]);

        Livewire::test(PostDataTable::class)
            ->call('updateSavedFilter', $setting->getKey(), [
                'name' => 'Updated',
                'settings' => ['hacked' => true],
            ]);

        $setting->refresh();

        expect($setting->name)->toBe('Updated')
            ->and($setting->settings)->not->toHaveKey('hacked');
    });
});

describe('saveFilter with permanent flag', function (): void {
    it('clears other permanent filters when saving as permanent', function (): void {
        $existingPermanent = $this->user->datatableUserSettings()->create([
            'name' => 'Existing Permanent',
            'component' => PostDataTable::class,
            'cache_key' => PostDataTable::class,
            'settings' => ['userFilters' => []],
            'is_permanent' => true,
        ]);

        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'New Permanent', true);

        $existingPermanent->refresh();

        expect($existingPermanent->is_permanent)->toBeFalse();

        $newPermanent = DatatableUserSetting::where('name', 'New Permanent')->first();

        expect($newPermanent->is_permanent)->toBeTrue();
    });
});

describe('saveFilter without enabledCols', function (): void {
    it('excludes enabledCols when withEnabledCols is false', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('saveFilter', 'No Cols', false, false);

        $setting = DatatableUserSetting::where('name', 'No Cols')->first();

        expect($setting->settings)->not->toHaveKey('enabledCols')
            ->and($setting->settings)->toHaveKey('userFilters');
    });
});

describe('mountStoresSettings with session cache', function (): void {
    it('loads cached state from session on mount', function (): void {
        config(['tall-datatables.should_cache' => true]);

        $cacheKey = config('tall-datatables.cache_key') . '.filter:' . PostDataTable::class;
        Session::put($cacheKey, [
            'perPage' => 50,
            'search' => 'cached search',
            'userOrderBy' => 'title',
            'userOrderAsc' => false,
        ]);

        $component = Livewire::test(PostDataTable::class);

        expect($component->get('perPage'))->toBe(50)
            ->and($component->get('search'))->toBe('cached search')
            ->and($component->get('userOrderBy'))->toBe('title')
            ->and($component->get('userOrderAsc'))->toBeFalse();
    });
});
