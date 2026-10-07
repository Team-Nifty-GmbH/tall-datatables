<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Traits\HasEloquentListeners;
use Tests\Fixtures\Models\BroadcastablePost;

#[Layout('components.layouts.app')]
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
