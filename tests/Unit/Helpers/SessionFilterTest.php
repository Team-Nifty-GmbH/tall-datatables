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

    it('is a plain class without Serializable interface', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Test');

        expect($filter)->toBeInstanceOf(SessionFilter::class);
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

    it('stores with correct session marker key', function (): void {
        $filter = SessionFilter::make(
            'custom-key',
            fn (Builder $query) => $query,
            'Filter'
        );

        $filter->store();

        expect(session()->get('custom-key_query'))->toBeTrue();
    });

    it('can retrieve stored filter from cache', function (): void {
        $filter = SessionFilter::make(
            'retrievable-key',
            fn (Builder $query) => $query->where('active', true),
            'Active Filter'
        );

        $filter->store();

        $retrieved = SessionFilter::retrieve('retrievable-key');

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

describe('SessionFilter native serialize and unserialize', function (): void {
    it('serialize preserves all properties', function (): void {
        $filter = SessionFilter::make('test-key', fn (Builder $query) => $query, 'Test Name');

        $serialized = serialize($filter);
        $restored = unserialize($serialized);

        expect($restored)
            ->toBeInstanceOf(SessionFilter::class);
        expect($restored->dataTableCacheKey)->toBe('test-key');
        expect($restored->name)->toBe('Test Name');
        expect($restored->loaded)->toBeFalse();
    });

    it('unserialize restores all properties', function (): void {
        $filter = SessionFilter::make('original', fn (Builder $query) => $query, 'Original');
        $filter->loaded = true;

        $serialized = serialize($filter);
        $restored = unserialize($serialized);

        expect($restored->dataTableCacheKey)->toBe('original');
        expect($restored->name)->toBe('Original');
        expect($restored->loaded)->toBeTrue();
    });

    it('serialize returns a string representation', function (): void {
        $filter = SessionFilter::make('test', fn (Builder $query) => $query, 'Filter');

        $serialized = serialize($filter);

        expect($serialized)->toBeString();
        $restored = unserialize($serialized);
        expect($restored)->toBeInstanceOf(SessionFilter::class)
            ->and($restored->name)->toBe('Filter')
            ->and($restored->dataTableCacheKey)->toBe('test');
    });

    it('unserialize restores from serialized string', function (): void {
        $filter = SessionFilter::make('from-str', fn (Builder $query) => $query, 'StringFilter');

        $serialized = serialize($filter);
        $restored = unserialize($serialized);

        expect($restored->name)->toBe('StringFilter');
        expect($restored->dataTableCacheKey)->toBe('from-str');
    });

    it('loaded defaults to false after round-trip', function (): void {
        $filter = SessionFilter::make('key', fn (Builder $query) => $query, 'test');
        $filter->loaded = false;

        $serialized = serialize($filter);
        $restored = unserialize($serialized);

        expect($restored->loaded)->toBeFalse();
    });
});

describe('SessionFilter constructor edge cases', function (): void {
    it('constructor wraps closure in SerializableClosure', function (): void {
        $filter = new SessionFilter(
            dataTableCacheKey: 'key',
            closure: fn () => 'test',
            name: 'Test'
        );

        expect($filter->closure)->toBeInstanceOf(SerializableClosure::class);
    });

    it('defaults loaded to false', function (): void {
        $filter = new SessionFilter(
            dataTableCacheKey: 'key',
            closure: fn () => null,
            name: 'Test'
        );

        expect($filter->loaded)->toBeFalse();
    });
});

describe('SessionFilter round-trip via store and retrieve', function (): void {
    it('restores from cache via retrieve method', function (): void {
        $filter = SessionFilter::make('test-key', fn (Builder $query) => $query, 'MyFilter');
        $filter->loaded = true;

        $filter->store();

        $restored = SessionFilter::retrieve('test-key');

        expect($restored->dataTableCacheKey)->toBe('test-key');
        expect($restored->name)->toBe('MyFilter');
        expect($restored->loaded)->toBeTrue();
    });
});

describe('SessionFilter setDataTableCacheKey with DataTable instance', function (): void {
    it('extracts cache key from DataTable instance', function (): void {
        $user = createTestUser();
        Illuminate\Support\Facades\Auth::login($user);

        $component = Livewire\Livewire::test(Tests\Fixtures\Livewire\PostDataTable::class);
        $instance = $component->instance();

        $filter = SessionFilter::make('temp', fn (Builder $query) => $query, 'Test');
        $filter->setDataTableCacheKey($instance);

        expect($filter->dataTableCacheKey)->toBe($instance->getCacheKey());
        expect($filter->dataTableCacheKey)->toBeString();
    });
});
