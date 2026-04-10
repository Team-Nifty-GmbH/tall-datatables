<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Tag;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('addDynamicJoin skips unsupported morph to many relation', function (): void {
    $post = createTestPost(['user_id' => $this->user->getKey()]);
    $tag = Tag::create(['name' => 'Laravel']);
    $post->tags()->attach($tag);

    $component = Livewire::test(PostDataTable::class)->instance();

    $query = Post::query();
    $method = new ReflectionMethod($component, 'addDynamicJoin');

    // Should not throw — returns the query unchanged
    $result = $method->invoke($component, $query, 'tags');

    expect($result)->toBeString();
    expect(Post::query()->get())->not->toBeEmpty();
});

test('addDynamicJoin works for supported belongs to relation', function (): void {
    $post = createTestPost(['user_id' => $this->user->getKey()]);

    $component = Livewire::test(PostDataTable::class)->instance();

    $query = Post::query();
    $method = new ReflectionMethod($component, 'addDynamicJoin');

    $table = $method->invoke($component, $query, 'user');

    expect($table)->toBe('users');
});

test('sorting by supported belongs to relation still works', function (): void {
    $post = createTestPost(['user_id' => $this->user->getKey()]);

    Livewire::test(PostDataTable::class)
        ->call('sortTable', 'user.name')
        ->assertOk()
        ->assertHasNoErrors();
});
