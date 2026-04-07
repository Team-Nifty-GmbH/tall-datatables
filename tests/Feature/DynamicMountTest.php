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

test('datatable renders when saved filter exists and mounted dynamically', function (): void {
    // Create a permanent filter so mountStoresSettings() calls
    // loadFilter() → loadData() after mount().
    $settingModel = config('tall-datatables.models.datatable_user_setting');
    $settingModel::create([
        'authenticatable_id' => $this->user->getKey(),
        'authenticatable_type' => $this->user->getMorphClass(),
        'component' => PostDataTable::class,
        'cache_key' => PostDataTable::class,
        'name' => 'Permanent filter',
        'is_permanent' => true,
        'is_layout' => false,
        'settings' => [
            'perPage' => 5,
        ],
    ]);

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

    // The permanent filter causes mountStoresSettings() → loadFilter() →
    // loadData() AFTER mount(). This second loadData() must not call
    // skipRender() or the component will never render.
    Livewire::test($parent::class)
        ->assertOk()
        ->call('showChild')
        ->assertSeeLivewire(PostDataTable::class);
});
