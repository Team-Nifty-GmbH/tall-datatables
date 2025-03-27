<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Contains the selected ids of the table rows.
     */
    public array $selected = [];

    public array $selectedIndex = [];

    public array $wildcardSelectExcluded = [];

    protected ?string $selectValue = null;

    protected function getSelectedActions(): array
    {
        return [];
    }

    #[Renderless]
    public function getSelectAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getSelectedModels(): Collection
    {
        return $this->getSelectedModelsQuery()->get();
    }

    protected function getSelectedModelsQuery(): Builder
    {
        return $this->getModel()::query()->whereIntegerInRaw($this->modelKeyName, $this->getSelectedValues());
    }

    protected function getSelectedValues(): array
    {
        return in_array('*', $this->selected)
            ? $this->buildSearch()
                ->whereKeyNot($this->wildcardSelectExcluded)
                ->pluck($this->modelKeyName)
                ->toArray()
            : $this->selected;
    }

    protected function getSelectValue(): string
    {
        return $this->selectValue ?? 'record.' . $this->modelKeyName;
    }
}
