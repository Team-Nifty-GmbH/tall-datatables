<?php

use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Illuminate\Database\Eloquent\Builder;

describe('SessionFilter', function (): void {
    it('can create a session filter using make', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'test-cache-key',
            closure: fn (Builder $query) => $query->where('is_active', true),
            name: 'Test Filter'
        );

        expect($filter)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($filter->name)->toBe('Test Filter');
    });

    it('stores dataTableCacheKey', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'my-datatable-key',
            closure: fn (Builder $query) => $query,
            name: 'Key Test'
        );

        expect($filter->dataTableCacheKey)->toBe('my-datatable-key');
    });

    it('returns the closure', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'test-key',
            closure: fn (Builder $query) => $query->where('status', 'published'),
            name: 'Published Filter'
        );

        expect($filter->getClosure())->toBeInstanceOf(Closure::class);
    });

    it('tracks loaded state', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'test-key',
            closure: fn (Builder $query) => $query,
            name: 'Loaded Filter'
        );

        expect($filter->loaded)->toBeFalse();

        $filter->loaded = true;

        expect($filter->loaded)->toBeTrue();
    });

    it('can set name', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'test-key',
            closure: fn (Builder $query) => $query,
            name: 'Original Name'
        );

        $filter->setName('New Name');

        expect($filter->name)->toBe('New Name');
    });

    it('can serialize and unserialize', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'serialize-test',
            closure: fn (Builder $query) => $query->where('test', true),
            name: 'Serialize Test'
        );

        $serialized = serialize($filter);
        $unserialized = unserialize($serialized);

        expect($unserialized)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($unserialized->name)->toBe('Serialize Test')
            ->and($unserialized->dataTableCacheKey)->toBe('serialize-test')
            ->and($unserialized->loaded)->toBeFalse();
    });

    it('can store to session', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'session-test',
            closure: fn (Builder $query) => $query,
            name: 'Session Test'
        );

        $filter->store();

        expect(session()->has('session-test_query'))->toBeTrue();
        expect(session()->get('session-test_query'))->toBeInstanceOf(SessionFilter::class);
    });

    it('can set closure', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'test-key',
            closure: fn (Builder $query) => $query,
            name: 'Test'
        );

        $newClosure = fn (Builder $query) => $query->where('new', true);
        $filter->setClosure($newClosure);

        expect($filter->getClosure())->toBeInstanceOf(Closure::class);
    });

    it('can set dataTableCacheKey', function (): void {
        $filter = SessionFilter::make(
            dataTableCacheKey: 'old-key',
            closure: fn (Builder $query) => $query,
            name: 'Test'
        );

        $filter->setDataTableCacheKey('new-key');

        expect($filter->dataTableCacheKey)->toBe('new-key');
    });
});
