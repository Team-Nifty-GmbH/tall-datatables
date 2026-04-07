<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Extended filter operators in DataTable', function (): void {
    it('filters with starts with operator', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Hello World']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Goodbye World']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'starts with', 'value' => 'Hello']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('filters with contains operator', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Hello World']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Goodbye Moon']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'contains', 'value' => 'World']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('filters with in operator using comma-separated values', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Gamma']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'in', 'value' => 'Alpha, Gamma']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(2);
    });

    it('filters with not in operator', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Beta']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Gamma']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'title', 'operator' => 'not in', 'value' => 'Alpha, Gamma']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});
