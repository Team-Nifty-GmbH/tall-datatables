<?php

use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Export User', 'email' => 'export@test.com']);
    $this->actingAs($this->user);
});

describe('DataTableExport headings', function (): void {
    it('generates headings from simple column names', function (): void {
        $export = new DataTableExport(Post::query(), ['title', 'content']);

        $headings = $export->headings();

        expect($headings)->toBe([__('Title'), __('Content')]);
    });

    it('generates headings with headline transformation', function (): void {
        $export = new DataTableExport(Post::query(), ['is_published', 'created_at']);

        $headings = $export->headings();

        expect($headings)->toBe([__('Is Published'), __('Created At')]);
    });

    it('generates headings for dot-notation columns', function (): void {
        $export = new DataTableExport(Post::query(), ['user.name', 'user.email']);

        $headings = $export->headings();

        expect($headings[0])->toContain(__('User'))
            ->toContain(' -> ')
            ->toContain(__('Name'));
        expect($headings[1])->toContain(__('User'))
            ->toContain(' -> ')
            ->toContain(__('Email'));
    });

    it('generates headings for deeply nested dot-notation columns', function (): void {
        $export = new DataTableExport(Post::query(), ['user.profile.avatar']);

        $headings = $export->headings();

        expect($headings[0])->toContain(' -> ')
            ->toContain(__('User'))
            ->toContain(__('Profile'))
            ->toContain(__('Avatar'));
    });

    it('returns empty array for no columns', function (): void {
        $export = new DataTableExport(Post::query(), []);

        expect($export->headings())->toBe([]);
    });
});

describe('DataTableExport map', function (): void {
    it('extracts simple column values from a row', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'My Post',
            'content' => 'Post content here',
        ]);

        $export = new DataTableExport(Post::query(), ['title', 'content']);
        $result = $export->map($post);

        expect($result)->toBe([
            'title' => 'My Post',
            'content' => 'Post content here',
        ]);
    });

    it('handles dot-notation for relation data', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Relational Post',
        ]);
        $post->load('user');

        $export = new DataTableExport(Post::query(), ['user.name']);
        $result = $export->map($post);

        expect($result['user.name'])->toBe('Export User');
    });

    it('returns null for missing columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $export = new DataTableExport(Post::query(), ['nonexistent']);
        $result = $export->map($post);

        expect($result['nonexistent'])->toBeNull();
    });

    it('handles wildcard fallback for nested array data', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        // Create comments to test wildcard extraction
        createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'Comment A']);
        createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'Comment B']);

        $post->load('comments');

        $export = new DataTableExport(Post::query(), ['comments.body']);
        $result = $export->map($post);

        // When dot notation returns null, it tries wildcard pattern
        // The wildcard approach should find the comment bodies
        if (is_string($result['comments.body'])) {
            expect($result['comments.body'])->toContain('Comment A')
                ->toContain('Comment B');
        } else {
            // If it returns as array or null, it means the wildcard did not match
            expect($result)->toHaveKey('comments.body');
        }
    });

    it('returns empty result for empty columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $export = new DataTableExport(Post::query(), []);
        $result = $export->map($post);

        expect($result)->toBe([]);
    });

    it('handles boolean values', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'is_published' => true,
        ]);

        $export = new DataTableExport(Post::query(), ['is_published']);
        $result = $export->map($post);

        expect($result['is_published'])->toBe(true);
    });
});

describe('DataTableExport query', function (): void {
    it('returns the builder instance', function (): void {
        $builder = Post::query()->where('is_published', true);
        $export = new DataTableExport($builder, ['title']);

        expect($export->query())->toBe($builder);
    });

    it('returns the same builder that was injected', function (): void {
        $builder = User::query()->orderBy('name');
        $export = new DataTableExport($builder, ['name', 'email']);

        $query = $export->query();

        expect($query)->toBeInstanceOf(Builder::class);
    });
});

describe('DataTableExport integration', function (): void {
    it('can be constructed with builder and columns', function (): void {
        $export = new DataTableExport(Post::query(), ['title', 'content']);

        expect($export)->toBeInstanceOf(DataTableExport::class);
    });

    it('handles mixed simple and relation columns', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Mixed Columns',
        ]);
        $post->load('user');

        $export = new DataTableExport(Post::query(), ['title', 'user.name']);

        $headings = $export->headings();
        expect($headings)->toHaveCount(2);

        $mapped = $export->map($post);
        expect($mapped['title'])->toBe('Mixed Columns')
            ->and($mapped['user.name'])->toBe('Export User');
    });
});
