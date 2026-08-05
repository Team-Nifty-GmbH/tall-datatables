<?php

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('only selects the key column when resolving a wildcard selection', function (): void {
    createTestPost(['user_id' => $this->user->getKey()]);

    $component = Livewire::test(PostDataTable::class)
        ->set('selected', ['*'])
        ->call('loadData');

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $component->instance()->getSelectedValues();

    $select = collect($queries)
        ->first(fn (string $sql): bool => str_contains($sql, 'from "posts"'));

    expect($select)->not->toBeNull()
        ->and($select)->toStartWith('select "posts"."id" from');
});

it('resolves a wildcard selection while ordering by an aggregate count column', function (): void {
    $post = createTestPost(['user_id' => $this->user->getKey()]);

    $component = Livewire::test(PostDataTable::class)
        ->set('enabledCols', ['title', 'comments_count'])
        ->set('userOrderBy', 'comments_count')
        ->set('selected', ['*'])
        ->call('loadData');

    expect($component->instance()->getSelectedValues())->toBe([$post->getKey()]);
});
