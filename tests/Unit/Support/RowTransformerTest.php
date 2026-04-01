<?php

use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Support\RowTransformer;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

describe('RowTransformer::transform', function (): void {
    it('returns a row array with raw value for plain string column', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => 'Hello World']);

        $row = $transformer->transform($post, ['title']);

        expect($row)->toHaveKey('title')
            ->and($row['title'])->toHaveKey('raw')
            ->and($row['title']['raw'])->toBe('Hello World');
    });

    it('omits display key when display equals escaped raw for plain strings', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => 'Simple Title']);

        $row = $transformer->transform($post, ['title']);

        expect($row['title'])->not->toHaveKey('display');
    });

    it('includes display key when formatter produces different output', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['is_published' => true]);

        $row = $transformer->transform($post, ['is_published']);

        // BooleanFormatter produces HTML, not "1"
        expect($row['is_published'])->toHaveKey('display');
    });

    it('transforms multiple columns at once', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => 'Test Post', 'is_published' => false]);

        $row = $transformer->transform($post, ['title', 'is_published']);

        expect($row)->toHaveKey('title')
            ->toHaveKey('is_published');
    });

    it('returns empty array for empty enabledCols', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost();

        $row = $transformer->transform($post, []);

        expect($row)->toBeEmpty();
    });

    it('handles null values gracefully', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['content' => null]);

        $row = $transformer->transform($post, ['content']);

        expect($row['content']['raw'])->toBeNull();
    });

    it('all display values are strings', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => 'Title', 'is_published' => true]);

        $row = $transformer->transform($post, ['title', 'is_published', 'created_at']);

        foreach ($row as $col => $cell) {
            if (array_key_exists('display', $cell)) {
                expect($cell['display'])->toBeString("Column '{$col}' display should be a string");
            }
        }
    });

    it('resolves casts from related model for relation columns', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $user = createTestUser(['name' => 'Jane Doe']);
        $post = createTestPost(['user_id' => $user->getKey()]);
        $post->load('user');

        $row = $transformer->transform($post, ['user.name']);

        expect($row)->toHaveKey('user.name')
            ->and($row['user.name']['raw'])->toBe('Jane Doe');
    });

    it('passes full model context to formatters', function (): void {
        $mockFormatter = new class() implements TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter
        {
            public ?array $context = null;

            public function format(mixed $value, array $context = []): string
            {
                $this->context = $context;

                return '<!-- captured -->';
            }
        };

        $registry = new FormatterRegistry();
        // Register for the BcFloat cast used by the 'price' column on Post
        $registry->register(TeamNiftyGmbH\DataTable\Casts\BcFloat::class, $mockFormatter);

        $post = createTestPost(['price' => 42.00]);

        $transformer = new RowTransformer($registry);
        $transformer->transform($post, ['price']);

        // Context should contain the model's attributes array
        expect($mockFormatter->context)->toBeArray()
            ->toHaveKey('title');
    });

    it('handles relation column with null related model', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        // Post without loaded relation
        $post = createTestPost();

        // Should not throw even if user relation isn't eagerly loaded
        $row = $transformer->transform($post, ['user.name']);

        expect($row)->toHaveKey('user.name');
    });

    it('handles deep nested relation columns gracefully', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $user = createTestUser(['name' => 'Nested User']);
        $post = createTestPost(['user_id' => $user->getKey()]);
        $comment = createTestComment(['post_id' => $post->getKey(), 'user_id' => $user->getKey()]);
        $comment->load('post.user');

        $row = $transformer->transform($comment, ['post.user.name']);

        expect($row)->toHaveKey('post.user.name');
    });

    it('omits display key when formatter escapes same as e(raw)', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => '<b>Bold</b>']);

        $row = $transformer->transform($post, ['title']);

        // StringFormatter calls e() which matches e(rawString), so display is omitted
        expect($row['title'])->not->toHaveKey('display');
        expect($row['title']['raw'])->toBe('<b>Bold</b>');
    });

    it('uses correct base column for dot-notation casts resolution', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $user = createTestUser(['name' => 'John']);
        $post = createTestPost(['user_id' => $user->getKey()]);
        $post->load('user');

        $row = $transformer->transform($post, ['user.name']);

        expect($row['user.name']['raw'])->toBe('John');
    });

    it('handles column with special characters in raw that produces same escaped output', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost(['title' => 'Normal Title']);

        $row = $transformer->transform($post, ['title']);

        // Normal text: display should be omitted because escaped raw = display
        expect($row['title'])->not->toHaveKey('display');
    });

    it('resolveCasts returns empty array for invalid relation', function (): void {
        $registry = new FormatterRegistry();
        $transformer = new RowTransformer($registry);

        $post = createTestPost();

        // nonexistent.field - relation does not exist, resolveCasts should catch exception
        $row = $transformer->transform($post, ['nonexistent.field']);

        expect($row)->toHaveKey('nonexistent.field')
            ->and($row['nonexistent.field']['raw'])->toBeNull();
    });
});
