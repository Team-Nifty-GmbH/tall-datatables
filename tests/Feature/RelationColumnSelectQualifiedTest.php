<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostWithTagsDataTable;
use Tests\Fixtures\Models\Tag;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('qualifies capped to-many select columns so pivot joins do not throw ambiguous column errors', function (): void {
    config(['tall-datatables.max_relation_column_values' => 50]);

    $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Tagged']);
    $post->tags()->attach(Tag::create(['name' => 'Laravel'])->getKey());
    $post->tags()->attach(Tag::create(['name' => 'Livewire'])->getKey());

    $data = Livewire::test(PostWithTagsDataTable::class)->instance()->getDataForTesting();

    $cell = $data['data'][0]['tags.name'] ?? [];
    $values = is_array($cell) && array_key_exists('raw', $cell) ? $cell['raw'] : $cell;

    expect($values)->toContain('Laravel', 'Livewire');
});
