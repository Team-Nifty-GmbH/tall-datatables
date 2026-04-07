<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\UserDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Relation Count Columns', function (): void {
    describe('withCount query', function (): void {
        it('adds withCount to query when enabledCols contains _count column', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post 1']);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Post 2']);

            $component = Livewire::test(UserDataTable::class)
                ->set('enabledCols', ['name', 'email', 'posts_count'])
                ->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBeGreaterThanOrEqual(1);

            $row = collect($data['data'])->first();
            $postsCount = is_array($row['posts_count'] ?? null)
                ? ($row['posts_count']['raw'] ?? $row['posts_count'])
                : ($row['posts_count'] ?? null);
            expect($postsCount)->toBe(2);
        });

        it('shows 0 for users with no related records', function (): void {
            $otherUser = createTestUser();

            $component = Livewire::test(UserDataTable::class)
                ->set('enabledCols', ['name', 'posts_count'])
                ->call('loadData');

            $data = $component->instance()->getDataForTesting();
            $rows = collect($data['data']);

            $otherRow = $rows->firstWhere('name', $otherUser->name);
            $count = is_array($otherRow['posts_count'] ?? null)
                ? ($otherRow['posts_count']['raw'] ?? $otherRow['posts_count'])
                : ($otherRow['posts_count'] ?? null);
            expect($count)->toBe(0);
        });
    });

    describe('filtering on count columns', function (): void {
        it('filters with greater than operator on count column', function (): void {
            $userWith3 = createTestUser();
            createTestPost(['user_id' => $userWith3->getKey(), 'title' => 'A']);
            createTestPost(['user_id' => $userWith3->getKey(), 'title' => 'B']);
            createTestPost(['user_id' => $userWith3->getKey(), 'title' => 'C']);

            $userWith1 = createTestUser();
            createTestPost(['user_id' => $userWith1->getKey(), 'title' => 'X']);

            $component = Livewire::test(UserDataTable::class)
                ->set('enabledCols', ['name', 'posts_count'])
                ->set('userFilters', [
                    [['column' => 'posts_count', 'operator' => '>', 'value' => 2]],
                ])
                ->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(1);
        });

        it('filters with equals operator on count column', function (): void {
            $userWith2 = createTestUser();
            createTestPost(['user_id' => $userWith2->getKey(), 'title' => 'A']);
            createTestPost(['user_id' => $userWith2->getKey(), 'title' => 'B']);

            $userWith0 = createTestUser();

            $component = Livewire::test(UserDataTable::class)
                ->set('enabledCols', ['name', 'posts_count'])
                ->set('userFilters', [
                    [['column' => 'posts_count', 'operator' => '=', 'value' => 0]],
                ])
                ->call('loadData');

            $data = $component->instance()->getDataForTesting();

            $names = collect($data['data'])->pluck('name')->toArray();
            expect($names)->toContain($userWith0->name)
                ->and($names)->not->toContain($userWith2->name);
        });
    });

    describe('column labels', function (): void {
        it('generates a label for count columns', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A']);

            $component = Livewire::test(UserDataTable::class)
                ->set('enabledCols', ['name', 'posts_count'])
                ->call('loadData');

            // Labels are regenerated from getColLabels() which uses enabledCols
            $reflection = new ReflectionMethod($component->instance(), 'getColLabels');
            $labels = $reflection->invoke($component->instance());
            expect($labels)->toHaveKey('posts_count');
        });
    });
});
