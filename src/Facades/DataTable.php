<?php

namespace TeamNiftyGmbH\DataTable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TeamNiftyGmbH\DataTable\DataTable
 */
class DataTable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TeamNiftyGmbH\DataTable\DataTable::class;
    }
}
