<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\SearchablePost;

class SearchablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    public bool $isFilterable = true;

    protected string $model = SearchablePost::class;
}
