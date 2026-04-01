<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Traits\HasEloquentListeners;
use Tests\Fixtures\Models\BroadcastablePost;

class BroadcastablePostDataTable extends DataTable
{
    use HasEloquentListeners;

    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    public bool $isFilterable = true;

    protected string $model = BroadcastablePost::class;
}
