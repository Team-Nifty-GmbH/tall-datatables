<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostWithCommentsDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

function commentValues(array $row): array
{
    $cell = $row['comments.body'] ?? [];
    if (is_array($cell) && array_key_exists('raw', $cell)) {
        $cell = $cell['raw'];
    }

    return is_array($cell) ? $cell : [$cell];
}

it('caps a to-many relation column per parent row', function (): void {
    config(['tall-datatables.max_relation_column_values' => 50]);

    foreach (range(1, 2) as $p) {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => "Post {$p}"]);
        foreach (range(1, 60) as $i) {
            createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey()]);
        }
    }

    $data = Livewire::test(PostWithCommentsDataTable::class)->instance()->getDataForTesting();

    expect($data['data'])->toHaveCount(2);
    foreach ($data['data'] as $row) {
        expect(count(commentValues($row)))->toBeLessThanOrEqual(50);
    }
});

it('does not cap when the limit is disabled', function (): void {
    config(['tall-datatables.max_relation_column_values' => 0]);

    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Uncapped']);
    foreach (range(1, 60) as $i) {
        createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey()]);
    }

    $data = Livewire::test(PostWithCommentsDataTable::class)->instance()->getDataForTesting();

    expect(count(commentValues($data['data'][0])))->toBe(60);
});

it('leaves to-one relation columns untouched', function (): void {
    config(['tall-datatables.max_relation_column_values' => 50]);

    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has User']);

    $data = Livewire::test(PostWithRelationsDataTable::class)->instance()->getDataForTesting();

    expect($data['data'][0])->toHaveKey('user.name');
});
