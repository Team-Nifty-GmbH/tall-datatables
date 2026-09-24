<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\UserDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

/**
 * A relation of a single word hides this: the column menu spells the relation the way it is
 * declared, and for "posts" that is the same either way. As soon as the relation has more
 * than one word, withCount names its result after Str::snake and the key the column is asked
 * for no longer exists on the row.
 */
describe('Count columns of a relation of more than one word', function (): void {
    it('puts the count on the key the column is asked for', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false]);

        $data = Livewire::test(UserDataTable::class)
            ->set('enabledCols', ['name', 'publishedPosts_count'])
            ->call('loadData')
            ->instance()
            ->getDataForTesting();

        $row = collect($data['data'])->firstWhere('name', $this->user->name);

        expect($row)->toHaveKey('publishedPosts_count')
            ->and($row['publishedPosts_count'])->toBe(2);
    });

    it('keeps the count on a column spelled in snake case', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);

        $data = Livewire::test(UserDataTable::class)
            ->set('enabledCols', ['name', 'published_posts_count'])
            ->call('loadData')
            ->instance()
            ->getDataForTesting();

        $row = collect($data['data'])->firstWhere('name', $this->user->name);

        expect($row)->toHaveKey('published_posts_count')
            ->and($row['published_posts_count'])->toBe(1);
    });

    it('filters on the count of such a relation', function (): void {
        $withTwo = createTestUser();
        createTestPost(['user_id' => $withTwo->getKey(), 'is_published' => true]);
        createTestPost(['user_id' => $withTwo->getKey(), 'is_published' => true]);

        $withNone = createTestUser();
        createTestPost(['user_id' => $withNone->getKey(), 'is_published' => false]);

        $data = Livewire::test(UserDataTable::class)
            ->set('enabledCols', ['name', 'publishedPosts_count'])
            ->set('userFilters', [
                [['column' => 'publishedPosts_count', 'operator' => '>', 'value' => 1]],
            ])
            ->call('loadData')
            ->instance()
            ->getDataForTesting();

        $names = collect($data['data'])->pluck('name')->toArray();

        expect($names)->toContain($withTwo->name)
            ->and($names)->not->toContain($withNone->name);
    });
});
