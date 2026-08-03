<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\CommentWithPostUserDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    $this->comment = createTestComment(['user_id' => $this->user->getKey()]);
});

it('qualifies relation filter columns so a through join does not throw ambiguous column errors', function (): void {
    $component = Livewire::test(CommentWithPostUserDataTable::class);
    $component->set('userFilters', [
        [['column' => 'post_user.id', 'operator' => '=', 'value' => $this->user->getKey()]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('id')->toArray())->toBe([$this->comment->getKey()]);
});

it('qualifies relation filter columns for is null filters', function (): void {
    $component = Livewire::test(CommentWithPostUserDataTable::class);
    $component->set('userFilters', [
        [['column' => 'post_user.deleted_at', 'operator' => 'is null', 'value' => null]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('id')->toArray())->toBe([$this->comment->getKey()]);
});

it('qualifies relation filter columns for negated filters', function (): void {
    $component = Livewire::test(CommentWithPostUserDataTable::class);
    $component->set('userFilters', [
        [['column' => 'post_user.id', 'operator' => '!=', 'value' => $this->user->getKey()]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect($data['data'])->toBeEmpty();
});

it('parses a quoted is null text filter as a null check', function (): void {
    $component = Livewire::test(CommentWithPostUserDataTable::class);
    $component->set('userFilters', ['text' => ['post_user.deleted_at' => '"is null"']]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('id')->toArray())->toBe([$this->comment->getKey()]);
});
