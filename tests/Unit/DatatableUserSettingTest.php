<?php

use Tests\Fixtures\Models\User;
use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('DatatableUserSetting Model', function (): void {
    it('can create a setting', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Test Setting',
            'component' => 'TestDataTable',
            'settings' => ['perPage' => 25, 'enabledCols' => ['name', 'email']],
        ]);

        expect($setting)
            ->toBeInstanceOf(DatatableUserSetting::class)
            ->and($setting->name)->toBe('Test Setting')
            ->and($setting->component)->toBe('TestDataTable');
    });

    it('casts settings to array', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Test Setting',
            'component' => 'TestDataTable',
            'settings' => ['perPage' => 25],
        ]);

        expect($setting->settings)
            ->toBeArray()
            ->toHaveKey('perPage')
            ->and($setting->settings['perPage'])->toBe(25);
    });

    it('casts is_layout to boolean', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Layout Setting',
            'component' => 'TestDataTable',
            'settings' => [],
            'is_layout' => 1,
        ]);

        expect($setting->is_layout)->toBeBool()->toBeTrue();
    });

    it('casts is_permanent to boolean', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Permanent Setting',
            'component' => 'TestDataTable',
            'settings' => [],
            'is_permanent' => 1,
        ]);

        expect($setting->is_permanent)->toBeBool()->toBeTrue();
    });

    it('sets authenticatable on creating', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Auto Auth Setting',
            'component' => 'TestDataTable',
            'settings' => [],
        ]);

        expect($setting->authenticatable_id)->toBe($this->user->getKey())
            ->and($setting->authenticatable_type)->toBe($this->user->getMorphClass());
    });

    it('has morphTo authenticatable relation', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Relation Test',
            'component' => 'TestDataTable',
            'settings' => [],
        ]);

        expect($setting->authenticatable)->toBeInstanceOf(User::class)
            ->and($setting->authenticatable->getKey())->toBe($this->user->getKey());
    });

    it('can store cache_key', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Cache Key Test',
            'component' => 'TestDataTable',
            'cache_key' => 'custom-cache-key',
            'settings' => [],
        ]);

        expect($setting->cache_key)->toBe('custom-cache-key');
    });

    it('can update settings', function (): void {
        $setting = DatatableUserSetting::create([
            'name' => 'Update Test',
            'component' => 'TestDataTable',
            'settings' => ['perPage' => 10],
        ]);

        $setting->update(['settings' => ['perPage' => 50]]);
        $setting->refresh();

        expect($setting->settings['perPage'])->toBe(50);
    });
});

describe('DatatableUserSetting Queries', function (): void {
    it('can query by component', function (): void {
        DatatableUserSetting::create([
            'name' => 'Setting 1',
            'component' => 'PostDataTable',
            'settings' => [],
        ]);

        DatatableUserSetting::create([
            'name' => 'Setting 2',
            'component' => 'UserDataTable',
            'settings' => [],
        ]);

        $postSettings = DatatableUserSetting::where('component', 'PostDataTable')->get();

        expect($postSettings)->toHaveCount(1)
            ->and($postSettings->first()->name)->toBe('Setting 1');
    });

    it('can query by cache_key', function (): void {
        DatatableUserSetting::create([
            'name' => 'Cached Setting',
            'component' => 'TestDataTable',
            'cache_key' => 'unique-key',
            'settings' => [],
        ]);

        $setting = DatatableUserSetting::where('cache_key', 'unique-key')->first();

        expect($setting)->not->toBeNull()
            ->and($setting->name)->toBe('Cached Setting');
    });

    it('can query layouts only', function (): void {
        DatatableUserSetting::create([
            'name' => 'Layout',
            'component' => 'TestDataTable',
            'settings' => [],
            'is_layout' => true,
        ]);

        DatatableUserSetting::create([
            'name' => 'Filter',
            'component' => 'TestDataTable',
            'settings' => [],
            'is_layout' => false,
        ]);

        $layouts = DatatableUserSetting::where('is_layout', true)->get();

        expect($layouts)->toHaveCount(1)
            ->and($layouts->first()->name)->toBe('Layout');
    });
});
