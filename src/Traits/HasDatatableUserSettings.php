<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDatatableUserSettings
{
    public function datatableUserSettings(): MorphMany
    {
        return $this->morphMany(config('tall-datatables.models.datatable_user_setting'), 'authenticatable');
    }

    public function getDataTableSettings(): Collection
    {
        return $this->datatableUserSettings()->get();
    }
}
