<?php

use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;

describe('HasFrontendAttributes', function (): void {
    test('icon returns an Icon instance', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        expect($model::icon())->toBeInstanceOf(Icon::class);
    });

    test('icon uses default no-symbol name', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        $icon = $model::icon();
        expect($icon->name)->toBe('no-symbol');
    });

    test('icon uses custom iconName property', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;

            protected static string $iconName = 'user';
        };

        $icon = $model::icon();
        expect($icon->name)->toBe('user');
    });

    test('setDetailRouteParams returns static', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        $result = $model->setDetailRouteParams(['id' => 1]);
        expect($result)->toBeInstanceOf($model::class);
    });

    test('detailRoute returns null when no route name defined', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        expect($model->detailRoute())->toBeNull();
    });
});
