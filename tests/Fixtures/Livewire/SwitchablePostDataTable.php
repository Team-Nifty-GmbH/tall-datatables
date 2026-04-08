<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class SwitchablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'is_published',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;

    protected function availableLayouts(): array
    {
        return ['table', 'grid'];
    }
}
