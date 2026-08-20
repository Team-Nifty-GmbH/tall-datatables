<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class TallPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    // Enough rows that the page itself scrolls, which is what a sticky head needs.
    public int $perPage = 50;

    protected string $model = Post::class;
}
