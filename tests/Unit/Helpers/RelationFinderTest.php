<?php

use Illuminate\Support\Collection;
use TeamNiftyGmbH\DataTable\Helpers\RelationFinder;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

describe('RelationFinder', function (): void {
    describe('forModel', function (): void {
        it('returns relations for a model instance', function (): void {
            $relations = RelationFinder::forModel(new Post());

            expect($relations)->toBeInstanceOf(Collection::class);
        });

        it('accepts a string class name', function (): void {
            $relations = RelationFinder::forModel(Post::class);

            expect($relations)->toBeInstanceOf(Collection::class);
            $names = $relations->pluck('name')->toArray();
            expect($names)->toContain('user');
        });
    });

    describe('relations', function (): void {
        it('finds BelongsTo relation on Post', function (): void {
            $relations = RelationFinder::forModel(new Post());
            $names = $relations->pluck('name')->toArray();

            expect($names)->toContain('user');
        });

        it('finds HasMany relation on User', function (): void {
            $relations = RelationFinder::forModel(new User());
            $names = $relations->pluck('name')->toArray();

            expect($names)->toContain('posts');
        });

        it('finds HasMany comments relation on Post', function (): void {
            $relations = RelationFinder::forModel(new Post());
            $names = $relations->pluck('name')->toArray();

            expect($names)->toContain('comments');
        });

        it('finds comments relation on User', function (): void {
            $relations = RelationFinder::forModel(new User());
            $names = $relations->pluck('name')->toArray();

            expect($names)->toContain('comments');
        });

        it('each relation has a name type and related class', function (): void {
            $relations = RelationFinder::forModel(new Post());

            $userRelation = $relations->firstWhere('name', 'user');

            expect($userRelation)->not->toBeNull()
                ->and($userRelation->related)->toBe(User::class);
        });

        it('returns a collection of Relation objects', function (): void {
            $finder = new RelationFinder();
            $relations = $finder->relations(new User());

            expect($relations)->toBeInstanceOf(Collection::class);
            $relations->each(function ($relation): void {
                expect($relation)->toBeInstanceOf(Spatie\ModelInfo\Relations\Relation::class);
            });
        });

        it('filters out methods that do not return relation types', function (): void {
            $relations = RelationFinder::forModel(new Post());
            $names = $relations->pluck('name')->toArray();

            expect($names)->not->toContain('getLabel')
                ->and($names)->not->toContain('getUrl')
                ->and($names)->not->toContain('getDescription');
        });

        it('includes relations on Comment model', function (): void {
            $relations = RelationFinder::forModel(new Tests\Fixtures\Models\Comment());

            expect($relations)->toBeInstanceOf(Collection::class);
            $names = $relations->pluck('name')->toArray();
            expect($names)->toContain('user')
                ->and($names)->toContain('post');
        });

        it('relation objects have type property', function (): void {
            $relations = RelationFinder::forModel(new Post());
            $userRelation = $relations->firstWhere('name', 'user');

            expect($userRelation->type)->toBeString()
                ->toContain('BelongsTo');
        });

        it('relation objects have related class property', function (): void {
            $relations = RelationFinder::forModel(new Post());
            $userRelation = $relations->firstWhere('name', 'user');

            expect($userRelation->related)->toBe(User::class);
        });

        it('discovers relations from return type declarations', function (): void {
            $relations = RelationFinder::forModel(new Post());

            // All relations on Post have explicit return type declarations
            expect($relations)->not->toBeEmpty();
            $names = $relations->pluck('name')->toArray();
            expect($names)->toContain('user')
                ->toContain('comments');
        });

        it('handles model with no relation resolvers', function (): void {
            $relations = RelationFinder::forModel(new Tests\Fixtures\Models\Comment());

            expect($relations)->toBeInstanceOf(Collection::class)
                ->not->toBeEmpty();
        });

        it('does not include non-relation methods that throw exceptions', function (): void {
            // Methods that invoke incorrectly should be filtered out (return null)
            $relations = RelationFinder::forModel(new Post());

            $names = $relations->pluck('name')->toArray();
            // Accessors and non-relation methods should not appear
            expect($names)->not->toContain('getLabel')
                ->not->toContain('getDescription')
                ->not->toContain('getUrl')
                ->not->toContain('getAvatarUrl');
        });

        it('handles Product model relations', function (): void {
            $relations = RelationFinder::forModel(new Tests\Fixtures\Models\Product());

            $names = $relations->pluck('name')->toArray();
            expect($names)->toContain('user');
        });

        it('discovers relations registered via resolveRelationUsing', function (): void {
            // Register a dynamic relation resolver on Post
            Post::resolveRelationUsing('dynamicRelation', function ($model) {
                return $model->belongsTo(User::class, 'user_id');
            });

            $relations = RelationFinder::forModel(new Post());
            $names = $relations->pluck('name')->toArray();

            expect($names)->toContain('dynamicRelation');
        });

        it('filters out null results from methods that throw exceptions', function (): void {
            // The relations method filters out null entries (from methods that throw)
            $relations = RelationFinder::forModel(new Post());

            // All returned relations should be non-null Relation instances
            $relations->each(function ($relation): void {
                expect($relation)->not->toBeNull()
                    ->toBeInstanceOf(Spatie\ModelInfo\Relations\Relation::class);
            });
        });
    });
});
