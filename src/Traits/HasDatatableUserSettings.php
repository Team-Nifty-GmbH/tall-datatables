<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDatatableUserSettings
{
    /**
     * @return MorphMany
     */
    public function datatableUserSettings(): MorphMany
    {
        return $this->morphMany(config('tall-datatables.models.datatable_user_setting'), 'authenticatable');
    }

    /**
     * @return Collection
     */
    public function getDataTableSettings(): Collection
    {
        return $this->datatableUserSettings()->get();
    }
}
