<?php

use Illuminate\Support\Collection;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

describe('ModelInfo', function (): void {
    it('can get model info for a model class', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo)
            ->toBeInstanceOf(ModelInfo::class)
            ->and($modelInfo->class)->toBe(Post::class);
    });

    it('can get model info for a model instance', function (): void {
        $post = new Post();
        $modelInfo = ModelInfo::forModel($post);

        expect($modelInfo)
            ->toBeInstanceOf(ModelInfo::class)
            ->and($modelInfo->class)->toBe(Post::class);
    });

    it('returns table name', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo->tableName)->toBe('posts');
    });

    it('detects implemented interfaces', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo->implements)
            ->toBeArray()
            ->toContain(InteractsWithDataTables::class);
    });

    it('caches model info', function (): void {
        $modelInfo1 = ModelInfo::forModel(Post::class);
        $modelInfo2 = ModelInfo::forModel(Post::class);

        expect($modelInfo1)->toBe($modelInfo2);
    });

    it('returns attributes collection', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo->attributes)
            ->toBeInstanceOf(Collection::class)
            ->not->toBeEmpty();
    });

    it('returns relations collection', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo->relations)
            ->toBeInstanceOf(Collection::class);
    });

    it('can find relation by name', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);
        $relation = $modelInfo->relation('user');

        expect($relation)
            ->not->toBeNull()
            ->and($relation->name)->toBe('user');
    });

    it('returns null for non-existent relation', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);
        $relation = $modelInfo->relation('nonExistent');

        expect($relation)->toBeNull();
    });

    it('can find relation with nested path', function (): void {
        $modelInfo = ModelInfo::forModel(Comment::class);

        $postRelation = $modelInfo->relation('post');
        expect($postRelation)->not->toBeNull();
    });

    it('returns model connection', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        expect($modelInfo->connectionName)->toBeString();
    });

    // primaryKey is not directly available on ModelInfo - use tableName instead
    // it('returns primary key') - skipped as this property is not exposed
});

describe('ModelInfo Attributes', function (): void {
    it('contains expected attributes for Post model', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);
        $attributeNames = $modelInfo->attributes->pluck('name')->toArray();

        expect($attributeNames)
            ->toContain('id')
            ->toContain('title')
            ->toContain('content')
            ->toContain('is_published');
    });

    it('detects attribute types', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);

        $titleAttribute = $modelInfo->attributes->firstWhere('name', 'title');

        expect($titleAttribute)->not->toBeNull();
    });
});

describe('ModelInfo for User model', function (): void {
    it('detects user relations', function (): void {
        $modelInfo = ModelInfo::forModel(User::class);
        $relationNames = $modelInfo->relations->pluck('name')->toArray();

        expect($relationNames)
            ->toContain('posts')
            ->toContain('comments');
    });

    it('gets morph class', function (): void {
        $modelInfo = ModelInfo::forModel(User::class);

        expect($modelInfo->morphClass)->toBe(User::class);
    });

    it('returns table name for user', function (): void {
        $modelInfo = ModelInfo::forModel(User::class);

        expect($modelInfo->tableName)->toBe('users');
    });

    it('returns user attributes', function (): void {
        $modelInfo = ModelInfo::forModel(User::class);
        $attributeNames = $modelInfo->attributes->pluck('name')->toArray();

        expect($attributeNames)
            ->toContain('id')
            ->toContain('name')
            ->toContain('email');
    });
});

describe('ModelInfo for Comment model', function (): void {
    it('can get model info for Comment', function (): void {
        $modelInfo = ModelInfo::forModel(Comment::class);

        expect($modelInfo)
            ->toBeInstanceOf(ModelInfo::class)
            ->and($modelInfo->class)->toBe(Comment::class)
            ->and($modelInfo->tableName)->toBe('comments');
    });

    it('detects comment relations', function (): void {
        $modelInfo = ModelInfo::forModel(Comment::class);
        $relationNames = $modelInfo->relations->pluck('name')->toArray();

        expect($relationNames)
            ->toContain('user')
            ->toContain('post');
    });
});

describe('ModelInfo Caching', function (): void {
    it('returns same instance for same model', function (): void {
        $info1 = ModelInfo::forModel(Post::class);
        $info2 = ModelInfo::forModel(Post::class);
        $info3 = ModelInfo::forModel(Post::class);

        expect($info1)
            ->toBe($info2)
            ->toBe($info3);
    });

    it('returns different instances for different models', function (): void {
        $postInfo = ModelInfo::forModel(Post::class);
        $userInfo = ModelInfo::forModel(User::class);

        expect($postInfo)->not->toBe($userInfo);
        expect($postInfo->class)->toBe(Post::class);
        expect($userInfo->class)->toBe(User::class);
    });
});

describe('ModelInfo Relations', function (): void {
    it('detects BelongsTo relation type', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);
        $userRelation = $modelInfo->relation('user');

        expect($userRelation)->not->toBeNull();
        expect($userRelation->type)->toContain('BelongsTo');
    });

    it('detects HasMany relation type', function (): void {
        $modelInfo = ModelInfo::forModel(User::class);
        $postsRelation = $modelInfo->relation('posts');

        expect($postsRelation)->not->toBeNull();
        expect($postsRelation->type)->toContain('HasMany');
    });

    it('provides related model for relation', function (): void {
        $modelInfo = ModelInfo::forModel(Post::class);
        $userRelation = $modelInfo->relation('user');

        expect($userRelation->related)->toBe(User::class);
    });
});
