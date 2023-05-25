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
        $dataTable = is_string($dataTable) ? $dataTable : get_class($dataTable);

        return $this->datatableUserSettings()
            ->where('component', $dataTable)
            ->get();
    }
}
