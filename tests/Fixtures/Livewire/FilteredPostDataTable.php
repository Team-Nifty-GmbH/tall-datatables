<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class FilteredPostDataTable extends DataTable
{
    public static array $testFilters = [];

    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'created_at',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;

    public function mount(): void
    {
        $this->filters = static::$testFilters;

        parent::mount();
    }
}
