<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class KanbanPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'is_published',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;

    public function kanbanMoveItem(int|string $id, string $targetLane): void
    {
        Post::findOrFail($id)->update(['is_published' => (bool) $targetLane]);
    }

    protected function availableLayouts(): array
    {
        return ['table', 'kanban'];
    }

    protected function kanbanColumn(): string
    {
        return 'is_published';
    }

    protected function kanbanLanes(): ?array
    {
        return [
            '1' => ['label' => 'Published', 'color' => 'emerald'],
            '0' => ['label' => 'Draft', 'color' => 'gray'],
        ];
    }
}
