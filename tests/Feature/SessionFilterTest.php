<?php

use Illuminate\Database\Eloquent\Builder;
use Laravel\SerializableClosure\SerializableClosure;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Tests\Fixtures\Models\Post;

describe('SessionFilter Construction', function (): void {
    it('can be created with make factory method', function (): void {
        $filter = SessionFilter::make(
            'test-key',
            fn (Builder $query) => $query->where('active', true),
            'Test Filter'
        );

        expect($filter)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($filter->name)->toBe('Test Filter')
            ->and($filter->dataTableCacheKey)->toBe('test-key');
    });

    it('is a plain class without Serializable interface', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');

        expect($filter)->toBeInstanceOf(SessionFilter::class);
    });

    it('wraps closure in SerializableClosure on construction', function (): void {
        $filter = new SessionFilter(
            'key',
            fn () => 'test',
            'name'
        );

        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });

    it('defaults loaded to false', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $q) => $q, 'Test');

        expect($filter->loaded)->toBeFalse();
    });

    it('can construct with loaded set to true', function (): void {
        $filter = new SessionFilter('key', fn () => null, 'name', true);

        expect($filter->loaded)->toBeTrue();
    });
});

describe('SessionFilter Setters', function (): void {
    it('can set datatable cache key from string', function (): void {
        $filter = SessionFilter::make('old', fn (Builder $q) => $q, 'Test');
        $result = $filter->setDataTableCacheKey('new-key');

        expect($filter->dataTableCacheKey)->toBe('new-key')
            ->and($result)->toBeInstanceOf(SessionFilter::class);
    });

    it('can set name', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $q) => $q, 'Old');
        $result = $filter->setName('New Name');

        expect($filter->name)->toBe('New Name')
            ->and($result)->toBeInstanceOf(SessionFilter::class);
    });

    it('can set closure and wraps it in SerializableClosure', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $q) => $q, 'Test');
        $newClosure = fn (Builder $q) => $q->where('status', 'active');
        $result = $filter->setClosure($newClosure);

        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class)
            ->and($result)->toBeInstanceOf(SessionFilter::class);
    });

    it('does not double-wrap an existing SerializableClosure', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $q) => $q, 'Test');
        $serializable = new SerializableClosure(fn (Builder $q) => $q->limit(10));
        $filter->setClosure($serializable);

        expect($filter->closure)->toBe($serializable);
    });

    it('supports fluent method chaining', function (): void {
        $filter = SessionFilter::make('initial', fn (Builder $q) => $q, 'Initial')
            ->setDataTableCacheKey('my-table')
            ->setName('My Filter')
            ->setClosure(fn (Builder $q) => $q->orderBy('id'));

        expect($filter->dataTableCacheKey)->toBe('my-table')
            ->and($filter->name)->toBe('My Filter')
            ->and($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });
});

describe('SessionFilter getClosure', function (): void {
    it('returns a Closure instance', function (): void {
        $filter = SessionFilter::make(
            'key',
            fn (Builder $q) => $q->where('test', true),
            'Test'
        );

        expect($filter->getClosure())->toBeInstanceOf(Closure::class);
    });
});

describe('SessionFilter Serialization', function (): void {
    it('can be serialized to string', function (): void {
        $filter = SessionFilter::make(
            'serialize-key',
            fn (Builder $q) => $q->where('published', true),
            'Serializable'
        );

        $serialized = serialize($filter);

        expect($serialized)->toBeString()->not->toBeEmpty();
    });

    it('can be unserialized back to SessionFilter', function (): void {
        $filter = SessionFilter::make(
            'round-trip-key',
            fn (Builder $q) => $q->where('active', true),
            'RoundTrip'
        );

        $unserialized = unserialize(serialize($filter));

        expect($unserialized)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($unserialized->name)->toBe('RoundTrip');
    });

    it('preserves closure after round-trip serialization', function (): void {
        $filter = SessionFilter::make(
            'closure-key',
            fn (Builder $q) => $q->where('status', 'draft'),
            'Draft'
        );

        $unserialized = unserialize(serialize($filter));

        expect($unserialized->getClosure())->toBeInstanceOf(Closure::class);
    });

    it('preserves loaded state after serialization', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $q) => $q, 'Test');
        $filter->loaded = true;

        $unserialized = unserialize(serialize($filter));

        expect($unserialized->loaded)->toBeTrue();
    });
});

describe('SessionFilter Session Storage', function (): void {
    it('stores filter in session', function (): void {
        $filter = SessionFilter::make(
            'store-key',
            fn (Builder $q) => $q->where('published', true),
            'Stored Filter'
        );

        $filter->store();

        expect(session()->has('store-key_query'))->toBeTrue();
    });

    it('stores itself in cache and sets session marker', function (): void {
        $filter = SessionFilter::make('value-key', fn (Builder $q) => $q, 'Value');

        $filter->store();

        expect(session()->get('value-key_query'))->toBeTrue();

        $stored = SessionFilter::retrieve('value-key');

        expect($stored)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($stored->name)->toBe('Value');
    });

    it('can be retrieved from cache after storing', function (): void {
        $filter = SessionFilter::make(
            'retrieve-key',
            fn (Builder $q) => $q->where('active', true),
            'Retrievable'
        );
        $filter->store();

        $retrieved = SessionFilter::retrieve('retrieve-key');

        expect($retrieved->name)->toBe('Retrievable')
            ->and($retrieved->dataTableCacheKey)->toBe('retrieve-key');
    });

    it('uses cache key with _query suffix as session key', function (): void {
        $filter = SessionFilter::make('my-table', fn (Builder $q) => $q, 'Test');
        $filter->store();

        expect(session()->has('my-table_query'))->toBeTrue()
            ->and(session()->has('my-table'))->toBeFalse();
    });
});

describe('SessionFilter with Real Query Execution', function (): void {
    beforeEach(function (): void {
        $user = createTestUser();
        createTestPost(['user_id' => $user->getKey(), 'is_published' => true, 'title' => 'Visible Post']);
        createTestPost(['user_id' => $user->getKey(), 'is_published' => false, 'title' => 'Hidden Post']);
    });

    it('applies closure filter to a real query', function (): void {
        $filter = SessionFilter::make(
            'real-query',
            fn (Builder $q) => $q->where('is_published', true),
            'Published Only'
        );

        $closure = $filter->getClosure();
        $query = Post::query();
        $closure($query);

        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Visible Post');
    });

    it('closure survives serialization and still filters correctly', function (): void {
        $filter = SessionFilter::make(
            'serial-query',
            fn (Builder $q) => $q->where('is_published', false),
            'Drafts'
        );

        $unserialized = unserialize(serialize($filter));
        $closure = $unserialized->getClosure();
        $query = Post::query();
        $closure($query);

        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Hidden Post');
    });
});
