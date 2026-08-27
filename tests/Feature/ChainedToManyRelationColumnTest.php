<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\ChainedToManyDataTable;

beforeEach(function (): void {
    Cache::flush();

    $this->user = createTestUser();
    $this->actingAs($this->user);

    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Root']);
    createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey()]);
});

function constructWithOf(object $component): array
{
    return (fn () => $this->constructWith())->call($component);
}

it('drops a column that chains more to-many relations than the cap allows', function (): void {
    $component = Livewire::test(ChainedToManyDataTable::class);

    expect($component->get('enabledCols'))
        ->not->toContain('comments.post.comments.user.posts.title');
});

it('does not eager load any hop of the chained to-many path', function (): void {
    $with = constructWithOf(Livewire::test(ChainedToManyDataTable::class)->instance())[0];

    // not even a partial hop of the dropped path may survive, each one multiplies
    // the hydrated object graph by max_relation_column_values
    expect($with)->each->not->toStartWith('comments.post');
});

it('keeps columns with a single to-many hop and none at all', function (): void {
    $component = Livewire::test(ChainedToManyDataTable::class);

    expect($component->get('enabledCols'))
        ->toContain('title')
        ->toContain('user.name')
        ->toContain('comments.body');
});

it('keeps a column with two to-many hops through distinct models', function (): void {
    $component = Livewire::test(ChainedToManyDataTable::class);

    expect($component->get('enabledCols'))
        ->toContain('comments.tags.name');
});

it('drops a column whose to-many hops return to a model the path already visited', function (): void {
    $component = Livewire::test(ChainedToManyDataTable::class);

    expect($component->get('enabledCols'))
        ->not->toContain('comments.post.comments.body');
});

it('keeps the chained column when the cap is disabled', function (): void {
    config(['tall-datatables.max_relation_column_to_many_hops' => 0]);

    $component = Livewire::test(ChainedToManyDataTable::class);

    expect($component->get('enabledCols'))
        ->toContain('comments.post.comments.user.posts.title')
        ->toContain('comments.post.comments.body');
});
