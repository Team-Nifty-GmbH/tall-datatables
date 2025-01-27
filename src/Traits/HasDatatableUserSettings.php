<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use TeamNiftyGmbH\DataTable\DataTable;

trait HasDatatableUserSettings
{
    public function datatableUserSettings(): MorphMany
    {
        return $this->morphMany(config('tall-datatables.models.datatable_user_setting'), 'authenticatable');
    }

    public function getDataTableSettings(string|DataTable $dataTable, ?Closure $query = null): Collection
    {
        return $this->datatableUserSettings()
            ->where('cache_key', is_string($dataTable) ? $dataTable : $dataTable->getCacheKey())
            ->where('component', is_string($dataTable) ? $dataTable : get_class($dataTable))
            ->when($query, $query)
            ->get();
    }
}
