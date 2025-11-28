<?php

use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

describe('InteractsWithDataTables Contract', function (): void {
    it('Post implements InteractsWithDataTables', function (): void {
        $post = new Post();

        expect($post)->toBeInstanceOf(InteractsWithDataTables::class);
    });

    it('User implements InteractsWithDataTables', function (): void {
        $user = new User();

        expect($user)->toBeInstanceOf(InteractsWithDataTables::class);
    });
});

describe('Post InteractsWithDataTables implementation', function (): void {
    it('returns title as label', function (): void {
        $post = createTestPost(['title' => 'My Test Post']);

        expect($post->getLabel())->toBe('My Test Post');
    });

    it('returns truncated content as description', function (): void {
        $longContent = str_repeat('a', 200);
        $post = createTestPost(['content' => $longContent]);

        expect($post->getDescription())
            ->toHaveLength(100);
    });

    it('returns null as avatar url', function (): void {
        $post = createTestPost();

        expect($post->getAvatarUrl())->toBeNull();
    });

    it('returns correct url', function (): void {
        $post = createTestPost();

        expect($post->getUrl())->toBe('/posts/' . $post->getKey());
    });
});

describe('User InteractsWithDataTables implementation', function (): void {
    it('returns name as label', function (): void {
        $user = createTestUser(['name' => 'John Doe']);

        expect($user->getLabel())->toBe('John Doe');
    });

    it('returns email as description', function (): void {
        $user = createTestUser(['email' => 'john@example.com']);

        expect($user->getDescription())->toBe('john@example.com');
    });

    it('returns null as avatar url', function (): void {
        $user = createTestUser();

        expect($user->getAvatarUrl())->toBeNull();
    });

    it('returns correct url', function (): void {
        $user = createTestUser();

        expect($user->getUrl())->toBe('/users/' . $user->getKey());
    });
});
