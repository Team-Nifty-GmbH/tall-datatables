<?php

use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('exports a to-one then to-many nested relation column', function (): void {
    $owner = createTestUser(['name' => 'Owner']);
    createTestComment(['user_id' => $owner->getKey(), 'body' => 'Topic A']);
    createTestComment(['user_id' => $owner->getKey(), 'body' => 'Topic B']);

    $post = createTestPost(['user_id' => $owner->getKey(), 'title' => 'Nested']);
    $post = Post::query()->with('user.comments')->find($post->getKey());

    $export = new DataTableExport(Post::query(), ['user.comments.body']);
    $row = $export->mapRow($post);

    expect($row['user.comments.body'])->toBeString()
        ->toContain('Topic A')
        ->toContain('Topic B');
});

it('still exports a single-level to-many relation column', function (): void {
    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'WithComments']);
    createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'First']);
    createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'Second']);

    $post = Post::query()->with('comments')->find($post->getKey());

    $export = new DataTableExport(Post::query(), ['comments.body']);
    $row = $export->mapRow($post);

    expect($row['comments.body'])->toContain('First')->toContain('Second');
});

it('exports a plain to-one relation column unchanged', function (): void {
    $owner = createTestUser(['name' => 'Solo Owner']);
    $post = createTestPost(['user_id' => $owner->getKey(), 'title' => 'Solo']);
    $post = Post::query()->with('user')->find($post->getKey());

    $export = new DataTableExport(Post::query(), ['user.name']);
    $row = $export->mapRow($post);

    expect($row['user.name'])->toBe('Solo Owner');
});
