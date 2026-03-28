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
});
