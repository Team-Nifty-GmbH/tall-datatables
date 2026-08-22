<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\AppendedPostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Serialization User', 'email' => 'serialize@example.com']);
    $this->actingAs($this->user);

    $post = createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Serialized Post',
        'content' => 'Content',
        'price' => 12.5,
        'is_published' => true,
    ]);

    $post->comments()->create(['user_id' => $this->user->getKey(), 'body' => 'A comment']);

    Post::$accessorCalls = 0;
});

test('keeps the columns a table asks for', function (): void {
    $row = Livewire::test(PostWithRelationsDataTable::class)->instance()->getDataForTesting()['data'][0];

    expect('Serialized Post')->toBe($row['title'])
        ->and('Serialization User')->toBe($row['user.name'])
        ->and('serialize@example.com')->toBe($row['user.email'])
        ->and(true)->toBe($row['is_published']['raw'])
        ->and(12.5)->toBe($row['price']['raw']);
});

test('keeps casts and date serialization intact', function (): void {
    $row = Livewire::test(PostWithRelationsDataTable::class)->instance()->getDataForTesting()['data'][0];

    expect($row['created_at']['raw'])->toBeString()
        ->and($row['created_at']['raw'])->not->toBeInstanceOf(Carbon\Carbon::class);

    expect(Post::query()->first()->toArray()['created_at'])->toBe($row['created_at']['raw']);
});

test('does not read appended attributes no column asked for', function (): void {
    Post::$accessorCalls = 0;

    $data = Livewire::test(AppendedPostDataTable::class)->instance()->getDataForTesting();

    expect(1)->toBe(count($data['data']))
        ->and(0)->toBe(Post::$accessorCalls);
});

test('leaves the model visibility as it found it', function (): void {
    $component = Livewire::test(PostWithRelationsDataTable::class)->instance();
    $component->getDataForTesting();

    expect([])->toBe(Post::query()->first()->getVisible());
});
