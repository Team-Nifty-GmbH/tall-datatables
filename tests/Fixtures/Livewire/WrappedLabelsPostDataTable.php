<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class WrappedLabelsPostDataTable extends DataTable
{
    public static bool $wrapColumnLabels = true;

    public array $enabledCols = [
        'title',
        'content',
    ];

    protected string $model = Post::class;
}
