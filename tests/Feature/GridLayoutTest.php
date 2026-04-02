<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\GridPostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Grid Layout Rendering', function (): void {
    it('renders the grid layout without errors', function (): void {
        Livewire::test(GridPostDataTable::class)
            ->assertStatus(200);
    });

    it('shows the empty state when no data exists', function (): void {
        Livewire::test(GridPostDataTable::class)
            ->call('loadData')
            ->assertSee(__('No data found'));
    });

    it('renders grid cards with data', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        Livewire::test(GridPostDataTable::class)
            ->call('loadData')
            ->assertSee($post->title);
    });

    it('renders multiple cards in the grid', function (): void {
        $posts = collect();
        for ($i = 0; $i < 3; $i++) {
            $posts->push(createTestPost(['user_id' => $this->user->getKey()]));
        }

        $component = Livewire::test(GridPostDataTable::class)
            ->call('loadData');

        $posts->each(function ($post) use ($component): void {
            $component->assertSee($post->title);
        });
    });

    it('uses the grid layout component', function (): void {
        $component = Livewire::test(GridPostDataTable::class);

        expect($component->instance()->getIslandData()['layout'])
            ->toBe('tall-datatables::layouts.grid');
    });
});

describe('Grid Layout Sorting', function (): void {
    it('can sort grid items', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha Post']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Zeta Post']);

        Livewire::test(GridPostDataTable::class)
            ->call('sortTable', 'title')
            ->assertSet('userOrderBy', 'title');
    });
});

describe('Grid Layout Selection', function (): void {
    it('supports selection on grid cards', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(GridPostDataTable::class)
            ->call('loadData');

        expect($component->get('isSelectable'))->toBeTrue();
        expect($component->get('selected'))->toBe([]);
    });
});

describe('Grid Layout Pagination', function (): void {
    it('renders pagination when enough items exist', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        $component = Livewire::test(GridPostDataTable::class)
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();

        expect($data['current_page'])->toBe(1);
        expect($data['last_page'])->toBeGreaterThan(1);
    });

    it('can navigate to next page', function (): void {
        for ($i = 0; $i < 20; $i++) {
            createTestPost(['user_id' => $this->user->getKey()]);
        }

        Livewire::test(GridPostDataTable::class)
            ->call('loadData')
            ->call('gotoPage', 2)
            ->assertSet('page', 2);
    });
});
