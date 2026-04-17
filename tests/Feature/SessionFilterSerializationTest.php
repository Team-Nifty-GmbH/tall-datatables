<?php

use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Tests\Fixtures\Livewire\PostDataTable;
use function Livewire\invade;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('SessionFilter survives serialization for queued exports', function (): void {
    it('applies session filter from object after unserialization without session', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published', 'is_published' => true]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Draft', 'is_published' => false]);

        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        SessionFilter::make(
            $instance->getCacheKey(),
            fn (Builder $query) => $query->where('is_published', true),
            'Published Only',
        )->store();

        // Load data with session filter — table shows filtered results
        $instance->loadData();
        $data = $instance->getDataForTesting();
        expect($data['total'])->toBe(1);
        expect($data['data'][0]['title'])->toBe('Published');

        // Simulate job context: serialize, clear session, unserialize
        $serialized = serialize($instance);
        session()->flush();

        $unserialized = unserialize($serialized);

        // buildSearch without session must still apply the filter
        $query = invade($unserialized)->buildSearch();
        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Published');
    });

    it('preserves session filter name after unserialization', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $instance = $component->instance();

        SessionFilter::make(
            $instance->getCacheKey(),
            fn (Builder $query) => $query->where('is_published', true),
            'My Filter Name',
        )->store();

        $instance->loadData();
        expect($instance->sessionFilter)->toBe(['name' => 'My Filter Name']);

        $serialized = serialize($instance);
        session()->flush();

        $unserialized = unserialize($serialized);
        expect($unserialized->sessionFilter)->toBe(['name' => 'My Filter Name']);
    });
});
