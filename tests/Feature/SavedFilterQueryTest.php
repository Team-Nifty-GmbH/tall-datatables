<?php

use TeamNiftyGmbH\DataTable\Support\SavedFilterQuery;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    $this->post = fn (string $title, bool $published = false): Post => Post::query()->create([
        'user_id' => $this->user->getKey(),
        'title' => $title,
        'content' => 'Content',
        'is_published' => $published,
    ]);

    $this->resolve = fn (array $filters): array => app(SavedFilterQuery::class)
        ->resolve(PostDataTable::class, ['userFilters' => $filters])
        ->pluck('posts.id')
        ->all();
});

test('a saved filter resolves to the rows it matches, and to nothing else', function (): void {
    $match = ($this->post)('Keep');
    ($this->post)('Leave');

    expect(($this->resolve)([[['column' => 'title', 'operator' => '=', 'value' => 'Keep']]]))
        ->toBe([$match->getKey()]);
});

test('two conditions in one group are combined with and', function (): void {
    $match = ($this->post)('Keep', true);
    ($this->post)('Keep');

    expect(($this->resolve)([[
        ['column' => 'title', 'operator' => '=', 'value' => 'Keep'],
        ['column' => 'is_published', 'operator' => '=', 'value' => 1],
    ]]))->toBe([$match->getKey()]);
});

test('two groups are combined with or', function (): void {
    $first = ($this->post)('First');
    $second = ($this->post)('Second');
    ($this->post)('Third');

    expect(($this->resolve)([
        [['column' => 'title', 'operator' => '=', 'value' => 'First']],
        [['column' => 'title', 'operator' => '=', 'value' => 'Second']],
    ]))->toEqualCanonicalizing([$first->getKey(), $second->getKey()]);
});

test('a filter without any condition is refused instead of quietly matching everything', function (array $settings): void {
    ($this->post)('Anything');

    expect(fn () => app(SavedFilterQuery::class)->resolve(PostDataTable::class, $settings))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'no filters' => [['userFilters' => []]],
    'only empty groups' => [['userFilters' => [[]]]],
    'no key at all' => [[]],
]);
