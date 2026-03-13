<?php

use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\Filters\FilterApplier;
use Tests\Fixtures\Models\Post;

describe('FilterApplier::apply', function (): void {
    it('applies a like filter and finds matching posts', function (): void {
        $applier = new FilterApplier();

        createTestPost(['title' => 'Hello Laravel']);
        createTestPost(['title' => 'Goodbye World']);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'title', 'operator' => 'like', 'value' => '%Laravel%'],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and($posts->first()->title)->toBe('Hello Laravel');
    });

    it('applies an exact match filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['title' => 'Exact Match']);
        createTestPost(['title' => 'Other Post']);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'title', 'operator' => '=', 'value' => 'Exact Match'],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and($posts->first()->title)->toBe('Exact Match');
    });

    it('applies a not-equal filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['title' => 'First']);
        createTestPost(['title' => 'Second']);
        createTestPost(['title' => 'Third']);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'title', 'operator' => '!=', 'value' => 'Second'],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(2);
        expect($posts->pluck('title')->toArray())->not->toContain('Second');
    });

    it('applies a between filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['price' => 50.00]);
        createTestPost(['price' => 150.00]);
        createTestPost(['price' => 250.00]);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'price', 'operator' => 'between', 'value' => [100, 200]],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and((float) $posts->first()->price)->toBe(150.0);
    });

    it('applies an is null filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['content' => null]);
        createTestPost(['content' => 'Some content']);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'content', 'operator' => 'is null', 'value' => null],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and($posts->first()->content)->toBeNull();
    });

    it('applies an is not null filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['content' => null]);
        createTestPost(['content' => 'Some content']);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'content', 'operator' => 'is not null', 'value' => null],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and($posts->first()->content)->toBe('Some content');
    });

    it('applies multiple filters combined with AND logic', function (): void {
        $applier = new FilterApplier();

        createTestPost(['title' => 'Published Post', 'is_published' => true]);
        createTestPost(['title' => 'Published Post', 'is_published' => false]);
        createTestPost(['title' => 'Other Post', 'is_published' => true]);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'title', 'operator' => '=', 'value' => 'Published Post'],
            ['column' => 'is_published', 'operator' => '=', 'value' => true],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1)
            ->and($posts->first()->is_published)->toBeTrue();
    });

    it('applies a relation column filter using whereHas', function (): void {
        $applier = new FilterApplier();

        $userA = createTestUser(['name' => 'Alice Smith']);
        $userB = createTestUser(['name' => 'Bob Jones']);

        createTestPost(['user_id' => $userA->getKey()]);
        createTestPost(['user_id' => $userB->getKey()]);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'user.name', 'operator' => 'like', 'value' => '%Alice%'],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(1);

        $posts->load('user');
        expect($posts->first()->user->name)->toBe('Alice Smith');
    });

    it('returns the same query builder instance', function (): void {
        $applier = new FilterApplier();

        $query = Post::query();
        $result = $applier->apply($query, []);

        expect($result)->toBeInstanceOf(Builder::class);
    });

    it('applies greater than filter', function (): void {
        $applier = new FilterApplier();

        createTestPost(['price' => 10.00]);
        createTestPost(['price' => 100.00]);
        createTestPost(['price' => 200.00]);

        $query = Post::query();
        $result = $applier->apply($query, [
            ['column' => 'price', 'operator' => '>', 'value' => 50],
        ]);

        $posts = $result->get();
        expect($posts)->toHaveCount(2);
    });
});
