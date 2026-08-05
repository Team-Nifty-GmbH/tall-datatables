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

    public function getSelectedValues(): array
    {
        // Narrow the query down to the key column before plucking. buildSearch() leaves a full
        // select list (and possibly withCount subselects) on the builder, and pluck() keeps an
        // existing select list instead of replacing it, so every column of every matching row
        // would be fetched. On large result sets that exhausts memory. Ordering is dropped for
        // the same reason: it is meaningless for a list of keys and may reference a select alias
        // that no longer exists.
        return in_array('*', $this->selected)
            ? $this->buildSearch(unpaginated: true)
                ->whereKeyNot($this->wildcardSelectExcluded)
                ->select($this->modelTable . '.' . $this->modelKeyName)
                ->reorder()
                ->pluck($this->modelKeyName)
                ->toArray()
            : $this->selected;
    }

    public function getSelectValue(): string
    {
        return $this->selectValue ?? 'record.' . $this->modelKeyName;
    }

    #[Renderless]
    public function toggleSelected(int|string $value): void
    {
        if (in_array('*', $this->selected)) {
            if (in_array($value, $this->wildcardSelectExcluded)) {
                $this->wildcardSelectExcluded = array_values(array_diff($this->wildcardSelectExcluded, [$value]));
            } else {
                $this->wildcardSelectExcluded[] = $value;
            }
        } elseif (in_array($value, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$value]));
        } else {
            $this->selected[] = $value;
        }
    }

    protected function getSelectedModels(): Collection
    {
        return $this->getSelectedModelsQuery()->get();
    }

    protected function getSelectedModelsQuery(): Builder
    {
        return $this->getModel()::query()->whereIntegerInRaw($this->modelKeyName, $this->getSelectedValues());
    }
}
