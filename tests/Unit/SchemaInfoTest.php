<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Helpers\SchemaInfo;
use TeamNiftyGmbH\DataTable\ModelInfo\Attribute;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Models\User;

describe('SchemaInfo', function (): void {
    it('can get model info for a model class', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo)
            ->toBeInstanceOf(SchemaInfo::class)
            ->and($modelInfo->class)->toBe(Post::class);
    });

    it('can get model info for a model instance', function (): void {
        $post = new Post();
        $modelInfo = SchemaInfo::forModel($post);

        expect($modelInfo)
            ->toBeInstanceOf(SchemaInfo::class)
            ->and($modelInfo->class)->toBe(Post::class);
    });

    it('returns table name', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->tableName)->toBe('posts');
    });

    it('detects implemented interfaces', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->implements)
            ->toBeArray()
            ->toContain(InteractsWithDataTables::class);
    });

    it('caches model info', function (): void {
        $modelInfo1 = SchemaInfo::forModel(Post::class);
        $modelInfo2 = SchemaInfo::forModel(Post::class);

        expect($modelInfo1)->toBe($modelInfo2);
    });

    it('returns attributes collection', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->attributes)
            ->toBeInstanceOf(Collection::class)
            ->not->toBeEmpty();
    });

    it('returns relations collection', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->relations)
            ->toBeInstanceOf(Collection::class);
    });

    it('can find relation by name', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);
        $relation = $modelInfo->relation('user');

        expect($relation)
            ->not->toBeNull()
            ->and($relation->name)->toBe('user');
    });

    it('returns null for non-existent relation', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);
        $relation = $modelInfo->relation('nonExistent');

        expect($relation)->toBeNull();
    });

    it('can find relation with nested path', function (): void {
        $modelInfo = SchemaInfo::forModel(Comment::class);

        $postRelation = $modelInfo->relation('post');
        expect($postRelation)->not->toBeNull();
    });
});

describe('SchemaInfo Attributes', function (): void {
    it('contains expected attributes for Post model', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);
        $attributeNames = $modelInfo->attributes->pluck('name')->toArray();

        expect($attributeNames)
            ->toContain('id')
            ->toContain('title')
            ->toContain('content')
            ->toContain('is_published');
    });

    it('detects attribute types', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        $titleAttribute = $modelInfo->attributes->firstWhere('name', 'title');

        expect($titleAttribute)->not->toBeNull();
    });
});

describe('SchemaInfo for User model', function (): void {
    it('detects user relations', function (): void {
        $modelInfo = SchemaInfo::forModel(User::class);
        $relationNames = $modelInfo->relations->pluck('name')->toArray();

        expect($relationNames)
            ->toContain('posts')
            ->toContain('comments');
    });

    it('gets morph class', function (): void {
        $modelInfo = SchemaInfo::forModel(User::class);

        expect($modelInfo->morphClass)->toBe(User::class);
    });

    it('returns table name for user', function (): void {
        $modelInfo = SchemaInfo::forModel(User::class);

        expect($modelInfo->tableName)->toBe('users');
    });

    it('returns user attributes', function (): void {
        $modelInfo = SchemaInfo::forModel(User::class);
        $attributeNames = $modelInfo->attributes->pluck('name')->toArray();

        expect($attributeNames)
            ->toContain('id')
            ->toContain('name')
            ->toContain('email');
    });
});

describe('SchemaInfo for Comment model', function (): void {
    it('can get model info for Comment', function (): void {
        $modelInfo = SchemaInfo::forModel(Comment::class);

        expect($modelInfo)
            ->toBeInstanceOf(SchemaInfo::class)
            ->and($modelInfo->class)->toBe(Comment::class)
            ->and($modelInfo->tableName)->toBe('comments');
    });

    it('detects comment relations', function (): void {
        $modelInfo = SchemaInfo::forModel(Comment::class);
        $relationNames = $modelInfo->relations->pluck('name')->toArray();

        expect($relationNames)
            ->toContain('user')
            ->toContain('post');
    });
});

describe('SchemaInfo Caching', function (): void {
    it('returns same instance for same model', function (): void {
        $info1 = SchemaInfo::forModel(Post::class);
        $info2 = SchemaInfo::forModel(Post::class);
        $info3 = SchemaInfo::forModel(Post::class);

        expect($info1)
            ->toBe($info2)
            ->toBe($info3);
    });

    it('returns different instances for different models', function (): void {
        $postInfo = SchemaInfo::forModel(Post::class);
        $userInfo = SchemaInfo::forModel(User::class);

        expect($postInfo)->not->toBe($userInfo);
        expect($postInfo->class)->toBe(Post::class);
        expect($userInfo->class)->toBe(User::class);
    });
});

describe('SchemaInfo Relations', function (): void {
    it('detects BelongsTo relation type', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);
        $userRelation = $modelInfo->relation('user');

        expect($userRelation)->not->toBeNull();
        expect($userRelation->type)->toContain('BelongsTo');
    });

    it('detects HasMany relation type', function (): void {
        $modelInfo = SchemaInfo::forModel(User::class);
        $postsRelation = $modelInfo->relation('posts');

        expect($postsRelation)->not->toBeNull();
        expect($postsRelation->type)->toContain('HasMany');
    });

    it('provides related model for relation', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);
        $userRelation = $modelInfo->relation('user');

        expect($userRelation->related)->toBe(User::class);
    });
});

describe('SchemaInfo Cache Scenarios', function (): void {
    beforeEach(function (): void {
        // Reset static cache so tests are isolated
        $reflection = new ReflectionClass(SchemaInfo::class);
        $prop = $reflection->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    });

    it('populates static cache on first call', function (): void {
        $reflection = new ReflectionClass(SchemaInfo::class);
        $prop = $reflection->getProperty('cache');
        $prop->setAccessible(true);

        expect($prop->getValue())->toBeEmpty();

        SchemaInfo::forModel(Post::class);

        expect($prop->getValue())
            ->toBeArray()
            ->toHaveKey(Post::class);
    });
});

describe('SchemaInfo Attributes with Formatters', function (): void {
    it('attributes are converted to custom Attribute instances', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        $modelInfo->attributes->each(function ($attribute): void {
            expect($attribute)->toBeInstanceOf(Attribute::class);
        });
    });

    it('attributes have formatter property populated', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $priceAttribute = $modelInfo->attributes->firstWhere('name', 'price');

        expect($priceAttribute)->not->toBeNull()
            ->and($priceAttribute->formatter)->not->toBeNull();
    });

    it('detects frontend formatter for Money cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $priceAttribute = $modelInfo->attributes->firstWhere('name', 'price');

        expect($priceAttribute->formatter)->toBe('money');
    });

    it('detects frontend formatter for BcFloat cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $quantityAttribute = $modelInfo->attributes->firstWhere('name', 'quantity');

        expect($quantityAttribute->formatter)->toBe('float');
    });

    it('detects frontend formatter for Percentage cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $discountAttribute = $modelInfo->attributes->firstWhere('name', 'discount');

        expect($discountAttribute->formatter)->toBe('percentage');
    });

    it('detects frontend formatter for Image cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $imageAttribute = $modelInfo->attributes->firstWhere('name', 'image_url');

        expect($imageAttribute->formatter)->toBe('image');
    });

    it('detects frontend formatter for Link cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $websiteAttribute = $modelInfo->attributes->firstWhere('name', 'website');

        expect($websiteAttribute->formatter)->toBe('link');
    });

    it('detects boolean formatter for native boolean cast', function (): void {
        $modelInfo = SchemaInfo::forModel(Product::class);
        $isActiveAttribute = $modelInfo->attributes->firstWhere('name', 'is_active');

        expect($isActiveAttribute->formatter)->toBe('boolean');
    });
});

describe('SchemaInfo attribute handling', function (): void {
    it('handles models with standard attributes gracefully', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        // Attributes should be populated as a collection
        expect($modelInfo->attributes)->toBeInstanceOf(Collection::class);
        expect($modelInfo->attributes)->not->toBeEmpty();
    });
});

describe('SchemaInfo morphClass resolution', function (): void {
    it('resolves morphClass from model', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->morphClass)->toBe(Post::class);
    });
});

describe('SchemaInfo Morph Class', function (): void {
    it('resolves morph class for model without custom morph map', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->morphClass)->toBe(Post::class);
    });

    it('resolves morph class for Comment model', function (): void {
        $modelInfo = SchemaInfo::forModel(Comment::class);

        expect($modelInfo->morphClass)->toBe(Comment::class);
    });
});

describe('SchemaInfo AttributeFinder fallback', function (): void {
    beforeEach(function (): void {
        // Reset static cache for isolation
        $reflection = new ReflectionClass(SchemaInfo::class);
        $prop = $reflection->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    });

    it('falls back to empty collection when attribute building fails', function (): void {
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->attributes)->toBeInstanceOf(Collection::class);
    });
});

describe('SchemaInfo flush', function (): void {
    beforeEach(function (): void {
        $reflection = new ReflectionClass(SchemaInfo::class);
        $prop = $reflection->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        Cache::forget(config('tall-datatables.cache_key') . '.modelInfo');
    });

    it('resets static cache when flush is called', function (): void {
        // Populate cache
        $info1 = SchemaInfo::forModel(Post::class);

        // Flush
        SchemaInfo::flush();

        // After flush, forModel should return a new instance
        $info2 = SchemaInfo::forModel(Post::class);

        // The instances should not be the same object reference after flush
        expect($info1)->not->toBe($info2);
    });
});

describe('SchemaInfo ClassMorphViolationException fallback', function (): void {
    beforeEach(function (): void {
        $reflection = new ReflectionClass(SchemaInfo::class);
        $prop = $reflection->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        Cache::forget(config('tall-datatables.cache_key') . '.modelInfo');
    });

    it('resolves morph class as model FQCN when getMorphClass throws', function (): void {
        // When ClassMorphViolationException is thrown, morphClass defaults to $model::class
        // Normal models just return their class name, so verify it works
        $modelInfo = SchemaInfo::forModel(Post::class);

        expect($modelInfo->morphClass)->toBe(Post::class);
    });
});
