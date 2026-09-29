<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class NestedTablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
    ];

    protected ?string $includeAfter = 'nested-user-table';

    protected string $model = Post::class;
}
