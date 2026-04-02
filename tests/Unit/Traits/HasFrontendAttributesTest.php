<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;
use Tests\Fixtures\Models\CustomRoutablePost;
use Tests\Fixtures\Models\RoutablePost;

describe('HasFrontendAttributes – icon', function (): void {
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

    test('icon name is lowercased', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;

            protected static string $iconName = 'DocumentText';
        };

        $icon = $model::icon();
        expect($icon->name)->toBe('documenttext');
    });
});

describe('HasFrontendAttributes – setDetailRouteParams', function (): void {
    test('setDetailRouteParams returns static for fluent API', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        $result = $model->setDetailRouteParams(['id' => 1]);
        expect($result)->toBeInstanceOf($model::class);
    });

    test('setDetailRouteParams stores the route params', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        $model->setDetailRouteParams(['id' => 42, 'slug' => 'test']);
        expect($model->detailRouteParams)->toBe(['id' => 42, 'slug' => 'test']);
    });

    test('setDetailRouteParams can be chained', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        $result = $model->setDetailRouteParams(['id' => 1])->setDetailRouteParams(['id' => 2]);
        expect($result->detailRouteParams)->toBe(['id' => 2]);
    });
});

describe('HasFrontendAttributes – detailRoute', function (): void {
    test('detailRoute returns null when no route name defined', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;
        };

        expect($model->detailRoute())->toBeNull();
    });

    test('detailRoute returns null when detailRouteName is null', function (): void {
        $model = new class() extends Model
        {
            use HasFrontendAttributes;

            protected ?string $detailRouteName = null;
        };

        expect($model->detailRoute())->toBeNull();
    });

    test('detailRoute returns absolute URL when route exists', function (): void {
        Route::get('/posts/{id}', fn () => 'ok')->name('posts.show');

        $model = new RoutablePost();
        $model->id = 5;

        $url = $model->detailRoute();
        expect($url)->toBeString()
            ->and($url)->toContain('/posts/5');
    });

    test('detailRoute returns relative URL when absolute is false', function (): void {
        Route::get('/posts/{id}', fn () => 'ok')->name('posts.show');

        $model = new RoutablePost();
        $model->id = 3;

        $url = $model->detailRoute(false);
        expect($url)->toBeString()
            ->and($url)->toBe('/posts/3');
    });
});

describe('HasFrontendAttributes – getDetailRouteParams', function (): void {
    test('getDetailRouteParams returns default id-based params', function (): void {
        $model = new RoutablePost();
        $model->id = 10;

        // RoutablePost does NOT define detailRouteParams() method
        // so it falls back to ['id' => $this->getKey()]
        $params = $model->getDetailRouteParams();
        expect($params)->toHaveKey('id', 10);
    });

    test('getDetailRouteParams uses custom detailRouteParams method when defined', function (): void {
        $model = new CustomRoutablePost();
        $model->id = 7;

        $params = $model->getDetailRouteParams();
        expect($params)->toHaveKey('slug', 'custom-slug');
    });

    test('getDetailRouteParams merges property and method params', function (): void {
        $model = new CustomRoutablePost();
        $model->id = 7;
        $model->setDetailRouteParams(['extra' => 'value']);

        $params = $model->getDetailRouteParams();
        expect($params)->toHaveKey('extra', 'value')
            ->and($params)->toHaveKey('slug', 'custom-slug');
    });

    test('getDetailRouteParams method overrides property params', function (): void {
        $model = new CustomRoutablePost();
        $model->id = 7;
        $model->setDetailRouteParams(['slug' => 'will-be-overridden']);

        $params = $model->getDetailRouteParams();
        // array_merge: method params override property params for same key
        expect($params['slug'])->toBe('custom-slug');
    });
});

describe('HasFrontendAttributes – typeScriptAttributes', function (): void {
    test('typeScriptAttributes returns an array', function (): void {
        $result = RoutablePost::typeScriptAttributes();
        expect($result)->toBeArray();
    });

    test('typeScriptAttributes keys are attribute names', function (): void {
        $result = RoutablePost::typeScriptAttributes();
        // The posts table has title, content, is_published, etc.
        expect($result)->toHaveKey('id');
    });
});
