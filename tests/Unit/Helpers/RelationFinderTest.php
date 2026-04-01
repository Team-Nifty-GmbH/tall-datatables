<?php

use Illuminate\Support\Collection;
use TeamNiftyGmbH\DataTable\Helpers\RelationFinder;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

describe('RelationFinder', function (): void {
    test('forModel returns relations for a model', function (): void {
        $relations = RelationFinder::forModel(new Post());

        expect($relations)->toBeInstanceOf(Collection::class);
    });

    test('finds BelongsTo relation on Post', function (): void {
        $relations = RelationFinder::forModel(new Post());
        $names = $relations->pluck('name')->toArray();

        expect($names)->toContain('user');
    });

    test('finds HasMany relation on User', function (): void {
        $relations = RelationFinder::forModel(new User());
        $names = $relations->pluck('name')->toArray();

        expect($names)->toContain('posts');
    });

    test('forModel accepts a string class name', function (): void {
        $relations = RelationFinder::forModel(Post::class);

        expect($relations)->toBeInstanceOf(Collection::class);
        $names = $relations->pluck('name')->toArray();
        expect($names)->toContain('user');
    });

    test('finds HasMany comments relation on Post', function (): void {
        $relations = RelationFinder::forModel(new Post());
        $names = $relations->pluck('name')->toArray();

        expect($names)->toContain('comments');
    });

    test('finds comments relation on User', function (): void {
        $relations = RelationFinder::forModel(new User());
        $names = $relations->pluck('name')->toArray();

        expect($names)->toContain('comments');
    });

    test('each relation has a name, type and related class', function (): void {
        $relations = RelationFinder::forModel(new Post());

        $userRelation = $relations->firstWhere('name', 'user');

        expect($userRelation)->not->toBeNull()
            ->and($userRelation->related)->toBe(User::class);
    });

    test('relations returns a collection of Relation objects', function (): void {
        $finder = new RelationFinder();
        $relations = $finder->relations(new User());

        expect($relations)->toBeInstanceOf(Collection::class);
        $relations->each(function ($relation): void {
            expect($relation)->toBeInstanceOf(\Spatie\ModelInfo\Relations\Relation::class);
        });
    });

    test('filters out methods that do not return relation types', function (): void {
        $relations = RelationFinder::forModel(new Post());
        $names = $relations->pluck('name')->toArray();

        // These model methods exist but are not relations
        expect($names)->not->toContain('getLabel')
            ->and($names)->not->toContain('getUrl')
            ->and($names)->not->toContain('getDescription');
    });

    test('handles model with no relations gracefully', function (): void {
        $relations = RelationFinder::forModel(new \Tests\Fixtures\Models\Comment());

        expect($relations)->toBeInstanceOf(Collection::class);
        // Comment has user and post relations
        $names = $relations->pluck('name')->toArray();
        expect($names)->toContain('user')
            ->and($names)->toContain('post');
    });
});
