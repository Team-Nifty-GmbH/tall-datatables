<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class PostWithBadgeDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    public array $formatters = [
        'title' => ['state', ['Published Post' => 'green', 'Draft Post' => 'red']],
    ];

    protected string $model = Post::class;
}
