<?php

use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Models\User;

describe('InteractsWithDataTables Contract', function (): void {
    it('defines getAvatarUrl method', function (): void {
        $reflection = new ReflectionClass(InteractsWithDataTables::class);

        expect($reflection->hasMethod('getAvatarUrl'))->toBeTrue();
        expect($reflection->getMethod('getAvatarUrl')->getReturnType()->getName())->toBe('string');
        expect($reflection->getMethod('getAvatarUrl')->getReturnType()->allowsNull())->toBeTrue();
    });

    it('defines getDescription method', function (): void {
        $reflection = new ReflectionClass(InteractsWithDataTables::class);

        expect($reflection->hasMethod('getDescription'))->toBeTrue();
        expect($reflection->getMethod('getDescription')->getReturnType()->getName())->toBe('string');
        expect($reflection->getMethod('getDescription')->getReturnType()->allowsNull())->toBeTrue();
    });

    it('defines getLabel method', function (): void {
        $reflection = new ReflectionClass(InteractsWithDataTables::class);

        expect($reflection->hasMethod('getLabel'))->toBeTrue();
        expect($reflection->getMethod('getLabel')->getReturnType()->getName())->toBe('string');
        expect($reflection->getMethod('getLabel')->getReturnType()->allowsNull())->toBeTrue();
    });

    it('defines getUrl method', function (): void {
        $reflection = new ReflectionClass(InteractsWithDataTables::class);

        expect($reflection->hasMethod('getUrl'))->toBeTrue();
        expect($reflection->getMethod('getUrl')->getReturnType()->getName())->toBe('string');
        expect($reflection->getMethod('getUrl')->getReturnType()->allowsNull())->toBeTrue();
    });

    it('is implemented by all test models', function (string $modelClass): void {
        $reflection = new ReflectionClass($modelClass);

        expect($reflection->implementsInterface(InteractsWithDataTables::class))->toBeTrue();
    })->with([
        'Post' => Post::class,
        'User' => User::class,
        'Product' => Product::class,
    ]);
});

describe('HasFrontendFormatter Contract', function (): void {
    it('defines getFrontendFormatter static method', function (): void {
        $reflection = new ReflectionClass(HasFrontendFormatter::class);

        expect($reflection->hasMethod('getFrontendFormatter'))->toBeTrue();
        expect($reflection->getMethod('getFrontendFormatter')->isStatic())->toBeTrue();
    });

    it('getFrontendFormatter returns string or array', function (): void {
        $reflection = new ReflectionClass(HasFrontendFormatter::class);
        $returnType = $reflection->getMethod('getFrontendFormatter')->getReturnType();

        expect($returnType)->toBeInstanceOf(ReflectionUnionType::class);

        $typeNames = array_map(fn ($t) => $t->getName(), $returnType->getTypes());
        expect($typeNames)->toContain('string');
        expect($typeNames)->toContain('array');
    });
});

describe('Product InteractsWithDataTables implementation', function (): void {
    it('returns product name as label', function (): void {
        $product = createTestProduct(['name' => 'Widget Pro']);

        expect($product->getLabel())->toBe('Widget Pro');
    });

    it('returns product description as description', function (): void {
        $product = createTestProduct(['description' => 'A great widget']);

        expect($product->getDescription())->toBe('A great widget');
    });

    it('returns image url as avatar url', function (): void {
        $product = createTestProduct(['image_url' => 'https://example.com/img.png']);

        expect($product->getAvatarUrl())->toBe('https://example.com/img.png');
    });

    it('returns correct url', function (): void {
        $product = createTestProduct();

        expect($product->getUrl())->toBe('/products/' . $product->getKey());
    });
});
