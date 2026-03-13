<?php

use TeamNiftyGmbH\DataTable\Support\ColumnResolver;
use Tests\Fixtures\Models\Post;
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
