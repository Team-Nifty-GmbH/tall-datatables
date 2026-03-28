<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;

describe('HasDatatableUserSettings', function (): void {
    test('datatableUserSettings returns MorphMany relation', function (): void {
        $user = createTestUser();

        expect($user->datatableUserSettings())->toBeInstanceOf(MorphMany::class);
    });

    test('user can have datatable settings', function (): void {
        $user = createTestUser();

        $user->datatableUserSettings()->create([
            'name' => 'test-filter',
            'component' => 'TestComponent',
            'cache_key' => 'test-cache-key',
            'settings' => ['enabledCols' => ['name', 'email']],
            'is_layout' => false,
            'is_permanent' => false,
        ]);

        expect($user->datatableUserSettings)->toHaveCount(1);
        expect($user->datatableUserSettings->first()->name)->toBe('test-filter');
    });

    test('getDataTableSettings returns settings collection', function (): void {
        $user = createTestUser();
        $this->actingAs($user);

        $user->datatableUserSettings()->create([
            'name' => 'test',
            'component' => 'TestComponent',
            'cache_key' => 'TestComponent',
            'settings' => [],
            'is_layout' => false,
            'is_permanent' => false,
        ]);

        $settings = $user->getDataTableSettings('TestComponent');
        expect($settings)->toHaveCount(1);
    });
});
