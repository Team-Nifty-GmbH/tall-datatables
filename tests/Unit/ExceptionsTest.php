<?php

use TeamNiftyGmbH\DataTable\Exceptions\LockedPublicPropertyTamperException;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;

describe('MissingTraitException', function (): void {
    it('creates exception with correct message', function (): void {
        $exception = MissingTraitException::create('App\\Models\\User', 'InteractsWithDataTables');

        expect($exception)
            ->toBeInstanceOf(MissingTraitException::class)
            ->and($exception->getMessage())->toBe('App\\Models\\User must use the InteractsWithDataTables trait');
    });

    it('extends base Exception class', function (): void {
        $exception = MissingTraitException::create('MyClass', 'SomeTrait');

        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('includes class name in message', function (): void {
        $exception = MissingTraitException::create('Order', 'HasFactory');

        expect($exception->getMessage())->toContain('Order');
    });

    it('includes trait name in message', function (): void {
        $exception = MissingTraitException::create('Product', 'Searchable');

        expect($exception->getMessage())->toContain('Searchable');
    });

    it('accepts namespaced class strings', function (): void {
        $exception = MissingTraitException::create('App\\Models\\Order', 'App\\Traits\\SomeTrait');

        expect($exception->getMessage())->toContain('App\\Models\\Order')
            ->and($exception->getMessage())->toContain('App\\Traits\\SomeTrait');
    });
});

describe('LockedPublicPropertyTamperException', function (): void {
    it('creates exception with property name in message', function (): void {
        $exception = LockedPublicPropertyTamperException::create('modelKeyName');

        expect($exception)
            ->toBeInstanceOf(LockedPublicPropertyTamperException::class)
            ->and($exception->getMessage())->toBe('You are not allowed to tamper with the protected property modelKeyName');
    });

    it('extends base Exception class', function (): void {
        $exception = LockedPublicPropertyTamperException::create('test');

        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('creates exception with empty property name', function (): void {
        $exception = LockedPublicPropertyTamperException::create();

        expect($exception->getMessage())->toBe('You are not allowed to tamper with the protected property ');
    });

    it('includes the property name in the message', function (): void {
        $exception = LockedPublicPropertyTamperException::create('selectedItems');

        expect($exception->getMessage())->toContain('selectedItems');
    });
});
