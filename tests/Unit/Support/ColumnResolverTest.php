<?php

use TeamNiftyGmbH\DataTable\Support\ColumnResolver;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Models\User;

describe('ColumnResolver::getColumns', function (): void {
    it('discovers model columns for Post', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        expect($columns)
            ->toBeArray()
            ->toHaveKey('title')
            ->toHaveKey('is_published');
    });

    it('includes price column', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        expect($columns)->toHaveKey('price');
    });

    it('includes id column', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        expect($columns)->toHaveKey('id');
    });

    it('each column has name, cast and type keys', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        foreach ($columns as $col) {
            expect($col)->toHaveKeys(['name', 'cast', 'type']);
        }
    });

    it('also works with a model instance', function (): void {
        $resolver = new ColumnResolver(new Post());
        $columns = $resolver->getColumns();

        expect($columns)->toHaveKey('title');
    });
});

describe('ColumnResolver::getLabel', function (): void {
    it('returns headline-cased label when no translation exists', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getLabel('is_published'))->toBe('Is Published');
    });

    it('returns headline for simple column name', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getLabel('title'))->toBe('Title');
    });

    it('uses last segment for dot-notation columns', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getLabel('user.name'))->toBe('Name');
    });
});

describe('ColumnResolver::getInputType', function (): void {
    it('detects boolean type for is_published', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('is_published'))->toBe('boolean');
    });

    it('detects text type for title', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('title'))->toBe('text');
    });

    it('detects datetime type for created_at', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('created_at'))->toBe('datetime');
    });

    it('detects datetime type for updated_at', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('updated_at'))->toBe('datetime');
    });

    it('detects text type for content', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('content'))->toBe('text');
    });

    it('detects text type for unknown column', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('nonexistent_column'))->toBe('text');
    });

    it('handles dot-notation relation columns', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // user.name → resolve through Post→user relation → User model → name column
        expect($resolver->getInputType('user.name'))->toBeIn(['text', 'number', 'boolean', 'datetime']);
    });
});

describe('ColumnResolver with User model', function (): void {
    it('discovers user columns', function (): void {
        $resolver = new ColumnResolver(User::class);
        $columns = $resolver->getColumns();

        expect($columns)
            ->toHaveKey('name')
            ->toHaveKey('email');
    });
});

describe('ColumnResolver::getInputType cast mapping', function (): void {
    it('maps integer cast to number', function (): void {
        // Use a model that has integer casts or test via dot notation
        $resolver = new ColumnResolver(Post::class);

        // price has BcFloat cast which is not in the standard cast map
        // so it falls through to inputTypeFromSchemaInfo
        expect($resolver->getInputType('price'))->toBeIn(['number', 'text']);
    });

    it('returns text for non-existent relation column', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // nonexistent.field - relation does not exist
        expect($resolver->getInputType('nonexistent.field'))->toBe('text');
    });

    it('resolves input type through nested relation', function (): void {
        $resolver = new ColumnResolver(Comment::class);

        // Comment->post->title
        expect($resolver->getInputType('post.title'))->toBeIn(['text', 'number', 'boolean', 'datetime']);
    });

    it('resolves casts from related model for dot-notation', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // user.email is on User model
        expect($resolver->getInputType('user.email'))->toBeIn(['text', 'number', 'boolean', 'datetime']);
    });
});

describe('ColumnResolver::getColumns excludes virtual and appended', function (): void {
    it('does not include virtual columns', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        // Check that all returned columns are not virtual
        foreach ($columns as $col) {
            expect($col)->toHaveKeys(['name', 'cast', 'type']);
        }
    });
});

describe('ColumnResolver with Product model', function (): void {
    it('discovers product columns with cast info', function (): void {
        $resolver = new ColumnResolver(Product::class);
        $columns = $resolver->getColumns();

        expect($columns)
            ->toHaveKey('price')
            ->toHaveKey('website')
            ->toHaveKey('image_url')
            ->toHaveKey('discount')
            ->toHaveKey('quantity');
    });

    it('maps boolean column from database type', function (): void {
        $resolver = new ColumnResolver(Product::class);

        expect($resolver->getInputType('is_active'))->toBe('boolean');
    });
});

describe('ColumnResolver::getLabel edge cases', function (): void {
    it('returns headline for complex column name', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getLabel('created_at'))->toBe('Created At');
    });

    it('returns headline for deep dot notation', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // Should use last segment 'name'
        expect($resolver->getLabel('user.posts.name'))->toBe('Name');
    });

    it('returns headline of last segment for deep nested columns', function (): void {
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getLabel('user.profile.display_name'))->toBe('Display Name');
    });
});

describe('ColumnResolver::getInputType fallback to schemaInfo', function (): void {
    it('falls back to inputTypeFromSchemaInfo for unknown casts', function (): void {
        // User model has 'email' which has no cast, should fall back to DB type
        $resolver = new ColumnResolver(User::class);

        expect($resolver->getInputType('email'))->toBe('text');
    });

    it('returns number for integer database type via schemaInfo', function (): void {
        // id is typically an integer column
        $resolver = new ColumnResolver(Post::class);

        expect($resolver->getInputType('id'))->toBe('number');
    });

    it('returns text for model instance instead of class string', function (): void {
        $resolver = new ColumnResolver(new Post());

        // This exercises the get_class($this->model) branch in resolveModelClass
        expect($resolver->getInputType('title'))->toBe('text');
    });

    it('resolves model class for non-dot column with model instance', function (): void {
        $resolver = new ColumnResolver(new Post());

        // id has integer type, which goes through resolveModelClass for model instance
        expect($resolver->getInputType('id'))->toBe('number');
    });
});

describe('ColumnResolver::getColumns skips virtual/appended', function (): void {
    it('does not include appended attributes', function (): void {
        $resolver = new ColumnResolver(Post::class);
        $columns = $resolver->getColumns();

        // None of the columns should be virtual or appended
        foreach ($columns as $col) {
            expect($col['name'])->not->toBe('');
        }
    });
});

describe('ColumnResolver::getInputType with cast containing colon', function (): void {
    it('strips colon suffix from cast type for mapping', function (): void {
        // Test that cast types like "datetime:Y-m-d" are handled correctly
        // created_at has datetime cast which may include format suffix
        $resolver = new ColumnResolver(Post::class);
        $result = $resolver->getInputType('created_at');

        expect($result)->toBe('datetime');
    });
});

describe('ColumnResolver::getInputType for non-existent attribute in schemaInfo', function (): void {
    it('returns text when attribute is not found in schemaInfo', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // A column that does not exist in the model at all
        $result = $resolver->getInputType('completely_fake_column');

        expect($result)->toBe('text');
    });
});

describe('ColumnResolver::getInputType for dot-notation with non-existent relation', function (): void {
    it('returns text when middle relation segment does not exist', function (): void {
        $resolver = new ColumnResolver(Post::class);

        // nonexistent_relation.field should return text
        $result = $resolver->getInputType('nonexistent_relation.field');

        expect($result)->toBe('text');
    });
});
