<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class RowClickPostDataTable extends DataTable
{
    public ?int $clickedId = null;

    public array $enabledCols = [
        'title',
    ];

    public bool $hasNoRedirect = true;

    protected string $model = Post::class;

    public function editRow(array $record = []): void
    {
        $this->clickedId = data_get($record, 'id');
        $this->islandsHaveMounted = false;
        $this->loadData(forceRender: true);
    }

    #[On('data-table-row-clicked'), Renderless]
    public function rowClicked(array $record = []): void
    {
        $this->clickedId = data_get($record, 'id');
    }

    protected function getListeners(): array
    {
        return array_merge(parent::getListeners(), ['data-table-row-edit' => 'editRow']);
    }
}
