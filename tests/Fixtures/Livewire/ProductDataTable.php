<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Product;

class ProductDataTable extends DataTable
{
    public array $enabledCols = [
        'name',
        'description',
        'price',
        'discount',
        'quantity',
        'is_active',
    ];

    public bool $isFilterable = true;

    protected string $model = Product::class;
}
