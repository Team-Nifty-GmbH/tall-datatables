<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class NonExportablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    public bool $isExportable = false;

    protected string $model = Post::class;
}
