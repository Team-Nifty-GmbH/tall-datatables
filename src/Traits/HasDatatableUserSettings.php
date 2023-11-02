<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use TeamNiftyGmbH\DataTable\DataTable;

trait HasDatatableUserSettings
{
    public function datatableUserSettings(): MorphMany
    {
        return $this->morphMany(config('tall-datatables.models.datatable_user_setting'), 'authenticatable');
    }

    public function getDataTableSettings(string|DataTable $dataTable): Collection
    {
        return $this->datatableUserSettings()
            ->where('cache_key', $dataTable->getCacheKey())
            ->where('component', get_class($dataTable))
            ->get();
    }
}
