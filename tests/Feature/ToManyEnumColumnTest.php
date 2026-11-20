<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostWithCommentStatusDataTable;
use Tests\Fixtures\Models\Comment;

it('formats every value of an enum column reached through a to-many relation', function (): void {
    $user = createTestUser();
    $this->actingAs($user);

    $post = createTestPost(['user_id' => $user->getKey(), 'title' => 'Two States']);
    Comment::create(['post_id' => $post->getKey(), 'user_id' => $user->getKey(), 'body' => 'draft']);
    Comment::create(['post_id' => $post->getKey(), 'user_id' => $user->getKey(), 'body' => 'published']);

    $data = Livewire::test(PostWithCommentStatusDataTable::class)
        ->instance()
        ->getDataForTesting();

    $cell = $data['data'][0]['comments.body'];

    expect($cell['raw'])->toBe(['draft', 'published'])
        ->and($cell['display'])->toContain('Draft')
        ->and($cell['display'])->toContain('Published');
});
