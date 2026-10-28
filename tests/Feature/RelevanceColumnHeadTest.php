<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\RankingScoreSearchableDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    config(['scout.driver' => 'collection']);
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Semantic Match Post']);
});

it('renders the score head cell together with the score cells of a search', function (): void {
    // The head lives outside the body island, so an island morph alone would
    // add the score cells without their head and shift every column by one.
    $component = Livewire::test(RankingScoreSearchableDataTable::class)
        ->call('loadData')
        ->set('search', 'Semantic');

    expect($component->effects['html'] ?? null)
        ->not->toBeNull()
        ->toContain(__('Score'));
});

it('renders the head again once a search without score cells follows', function (): void {
    $component = Livewire::test(RankingScoreSearchableDataTable::class)
        ->call('loadData')
        ->set('search', 'Semantic')
        ->set('search', '');

    expect($component->effects['html'] ?? null)
        ->not->toBeNull()
        ->not->toContain(__('Score'));
});

it('keeps to the island while the score column stays as it is', function (): void {
    $component = Livewire::test(RankingScoreSearchableDataTable::class)
        ->call('loadData')
        ->set('search', 'Semantic')
        ->set('search', 'Semantic Match');

    expect($component->effects['html'] ?? null)->toBeNull();
});
