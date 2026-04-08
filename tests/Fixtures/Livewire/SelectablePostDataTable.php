<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class SelectablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    public bool $isSelectable = true;

    protected string $model = Post::class;
}
