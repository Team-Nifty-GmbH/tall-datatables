<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\View\ComponentAttributeBag;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;

trait SupportsSelecting
{
    /**
     * If set to true the table rows will be selectable.
     */
    #[Locked]
    public bool $isSelectable = false;

    protected ?string $selectValue = null;

    /**
     * Contains the selected ids of the table rows.
     */
    public array $selected = [];

    public array $selectedIndex = [];

    public array $wildcardSelectExcluded = [];

    protected function getSelectedValues(): array
    {
        return in_array('*', $this->selected)
            ? $this->buildSearch()
                ->whereIntegerNotInRaw('id', $this->wildcardSelectExcluded)
                ->pluck('id')
                ->toArray()
            : $this->selected;
    }

    #[Renderless]
    public function getSelectAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getSelectedActions(): array
    {
        return [];
    }

    protected function getSelectValue(): string
    {
        return $this->selectValue ?? 'record.' . $this->modelKeyName;
    }
}
