<?php

use Illuminate\Database\Eloquent\Builder;
use Laravel\SerializableClosure\SerializableClosure;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Tests\Fixtures\Models\Post;

describe('SessionFilter', function (): void {
    it('can be created with make method', function (): void {
        $filter = SessionFilter::make(
            'test-datatable',
            fn (Builder $query) => $query->where('is_published', true),
            'Published Posts'
        );

        expect($filter)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($filter->name)->toBe('Published Posts')
            ->and($filter->dataTableCacheKey)->toBe('test-datatable');
    });

    it('implements Serializable interface', function (): void {
        expect(SessionFilter::class)->toImplement(Serializable::class);
    });

    it('can set datatable cache key from string', function (): void {
        $filter = SessionFilter::make('initial', fn (Builder $query) => $query, 'Test');
        $filter->setDataTableCacheKey('my-datatable');

        expect($filter->dataTableCacheKey)->toBe('my-datatable');
    });

    it('can set name', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Initial');
        $filter->setName('My Custom Filter');

        expect($filter->name)->toBe('My Custom Filter');
    });

    it('can set closure', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');
        $closure = fn (Builder $query) => $query->where('active', true);
        $filter->setClosure($closure);

        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });

    it('wraps closure in SerializableClosure', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');
        $closure = fn (Builder $query) => $query->where('status', 'active');
        $filter->setClosure($closure);

        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });

    it('does not double-wrap SerializableClosure', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');
        $serializableClosure = new SerializableClosure(fn (Builder $query) => $query);
        $filter->setClosure($serializableClosure);

        expect($filter->closure)->toBe($serializableClosure);
    });

    it('can get closure back', function (): void {
        $originalClosure = fn (Builder $query) => $query->where('test', true);
        $filter = SessionFilter::make('test', $originalClosure, 'Test');

        $retrievedClosure = $filter->getClosure();

        expect($retrievedClosure)->toBeInstanceOf(Closure::class);
    });

    it('has loaded property defaulting to false', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');

        expect($filter->loaded)->toBeFalse();
    });

    it('can mark filter as loaded', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');
        $filter->loaded = true;

        expect($filter->loaded)->toBeTrue();
    });
});

describe('SessionFilter Serialization', function (): void {
    it('can be serialized', function (): void {
        $filter = SessionFilter::make(
            'test-datatable',
            fn (Builder $query) => $query->where('published', true),
            'Published Filter'
        );

        $serialized = serialize($filter);

        expect($serialized)->toBeString();
    });

    it('can be unserialized', function (): void {
        $filter = SessionFilter::make(
            'test-datatable',
            fn (Builder $query) => $query->where('published', true),
            'Published Filter'
        );

        $serialized = serialize($filter);
        $unserialized = unserialize($serialized);

        expect($unserialized)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($unserialized->name)->toBe('Published Filter')
            ->and($unserialized->dataTableCacheKey)->toBe('test-datatable');
    });

    it('preserves closure after serialization', function (): void {
        $filter = SessionFilter::make(
            'test',
            fn (Builder $query) => $query->where('status', 'active'),
            'Test'
        );

        $serialized = serialize($filter);
        $unserialized = unserialize($serialized);

        expect($unserialized->getClosure())->toBeInstanceOf(Closure::class);
    });

    it('preserves loaded state after serialization', function (): void {
        $filter = SessionFilter::make(
            'test',
            fn (Builder $query) => $query,
            'Test'
        );
        $filter->loaded = true;

        $serialized = serialize($filter);
        $unserialized = unserialize($serialized);

        expect($unserialized->loaded)->toBeTrue();
    });
});

describe('SessionFilter Session Storage', function (): void {
    it('can store filter in session', function (): void {
        $filter = SessionFilter::make(
            'post-datatable',
            fn (Builder $query) => $query->where('is_published', true),
            'Published Posts'
        );

        $filter->store();

        expect(session()->has('post-datatable_query'))->toBeTrue();
    });

    it('stores with correct session key', function (): void {
        $filter = SessionFilter::make(
            'custom-key',
            fn (Builder $query) => $query,
            'Filter'
        );

        $filter->store();

        expect(session()->get('custom-key_query'))->toBeInstanceOf(SessionFilter::class);
    });

    it('can retrieve stored filter from session', function (): void {
        $filter = SessionFilter::make(
            'retrievable-key',
            fn (Builder $query) => $query->where('active', true),
            'Active Filter'
        );

        $filter->store();

        $retrieved = session()->get('retrievable-key_query');

        expect($retrieved)
            ->toBeInstanceOf(SessionFilter::class)
            ->and($retrieved->name)->toBe('Active Filter');
    });
});

describe('SessionFilter Method Chaining', function (): void {
    it('supports fluent api', function (): void {
        $filter = SessionFilter::make('initial', fn (Builder $query) => $query, 'Initial')
            ->setDataTableCacheKey('my-table')
            ->setName('My Filter')
            ->setClosure(fn (Builder $query) => $query);

        expect($filter->dataTableCacheKey)->toBe('my-table');
        expect($filter->name)->toBe('My Filter');
        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });
});

describe('SessionFilter with Real Query', function (): void {
    beforeEach(function (): void {
        $user = createTestUser();
        createTestPost(['user_id' => $user->getKey(), 'is_published' => true, 'title' => 'Published']);
        createTestPost(['user_id' => $user->getKey(), 'is_published' => false, 'title' => 'Draft']);
    });

    it('closure can filter query', function (): void {
        $filter = SessionFilter::make(
            'test',
            fn (Builder $query) => $query->where('is_published', true),
            'Published Only'
        );

        $closure = $filter->getClosure();
        $query = Post::query();
        $closure($query);

        expect($query->count())->toBe(1);
        expect($query->first()->title)->toBe('Published');
    });
});
