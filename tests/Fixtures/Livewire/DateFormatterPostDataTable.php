<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class DateFormatterPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'date_col',
        'datetime_col',
        'immutable_date_col',
        'immutable_dt_col',
    ];

    public array $formatters = [
        'date_col' => 'date',
        'datetime_col' => 'datetime',
        'immutable_date_col' => 'immutable_date',
        'immutable_dt_col' => 'immutable_datetime',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;
}
