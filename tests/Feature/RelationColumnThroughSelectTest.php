<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\CommentWithPostUserDataTable;
use Tests\Fixtures\Livewire\PostWithCommentsDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('qualifies through relation select columns so the through join does not throw ambiguous column errors', function (): void {
    $instance = Livewire::test(CommentWithPostUserDataTable::class)->instance();

    $constructWith = new ReflectionMethod($instance, 'constructWith');
    $constructWith->setAccessible(true);

    expect($constructWith->invoke($instance)[0])->toContain('postUser:users.id,users.name');
});

it('qualifies relation select columns for plain relations as well', function (): void {
    $instance = Livewire::test(PostWithCommentsDataTable::class)->instance();

    $constructWith = new ReflectionMethod($instance, 'constructWith');
    $constructWith->setAccessible(true);

    expect($constructWith->invoke($instance)[0])->toContain('comments:comments.post_id,comments.body');
});
