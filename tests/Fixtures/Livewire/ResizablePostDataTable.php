<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class ResizablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'created_at',
    ];

    public bool $isSelectable = true;

    protected string $model = Post::class;

    protected function isResizable(): bool
    {
        return true;
    }
}
