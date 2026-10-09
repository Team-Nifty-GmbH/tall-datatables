<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\ChainedToManyDataTable;
use Tests\Fixtures\Models\Tag;

beforeEach(function (): void {
    Cache::flush();

    $this->user = createTestUser();
    $this->actingAs($this->user);

    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Root']);
    $comment = createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey()]);
    $comment->tags()->attach(Tag::create(['name' => 'Urgent'])->getKey());
});

function firstRowOf(object $component): object
{
    return (fn () => $this->buildSearch()->get())->call($component)->first();
}

it('loads the value behind two to-many hops', function (): void {
    $row = firstRowOf(Livewire::test(ChainedToManyDataTable::class)->instance());

    expect($row->comments->first()->tags->first()?->name)->toBe('Urgent');
});
