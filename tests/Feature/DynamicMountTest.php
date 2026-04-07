<?php

use Livewire\Component;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    for ($i = 0; $i < 3; $i++) {
        createTestPost(['user_id' => $this->user->getKey()]);
    }
});

test('datatable renders when mounted as dynamic component via call', function (): void {
    $parent = new class() extends Component
    {
        public string $child = '';

        public function render(): string
        {
            return <<<'BLADE'
            <div>
                @if($child)
                    <livewire:is :component="$child" :key="$child" />
                @endif
            </div>
            BLADE;
        }

        public function showChild(): void
        {
            $this->child = 'post-data-table';
        }
    };

    Livewire::component('post-data-table', PostDataTable::class);

    Livewire::test($parent::class)
        ->assertOk()
        ->call('showChild')
        ->assertSeeLivewire(PostDataTable::class);
});

test('loadData accepts forceRender parameter', function (): void {
    $component = Livewire::test(PostDataTable::class)->instance();

    // loadData must accept forceRender so mount() can prevent skipRender
    $component->loadData(forceRender: true);

    $data = $component->getDataForTesting();

    expect($data)->not->toBeEmpty();
});
