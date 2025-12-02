<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

trait SupportsGrouping
{
    public int $currentGroupsPage = 1;

    public array $expandedGroups = [];

    public ?string $groupBy = null;

    public array $groupPages = [];

    public int $groupPerPage = 5;

    public int $groupsPerPage = 25;

    #[Renderless]
    public function getGroupableCols(): array
    {
        $modelInfo = ModelInfo::forModel($this->getModel());

        return $modelInfo->attributes
            ->filter(fn ($attribute) => ! $attribute->virtual)
            ->pluck('name')
            ->values()
            ->toArray();
    }

    #[Renderless]
    public function setGroupBy(?string $column): void
    {
        $this->groupBy = $column;
        $this->groupPages = [];
        $this->expandedGroups = [];
        $this->currentGroupsPage = 1;

        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function setGroupPage(string $groupKey, int $page): void
    {
        $this->groupPages[$groupKey] = $page;
        $this->loadData();
    }

    #[Renderless]
    public function setGroupsPage(int $page): void
    {
        $this->currentGroupsPage = $page;
        $this->loadData();
    }

    #[Renderless]
    public function toggleGroup(string $groupKey): void
    {
        if (in_array($groupKey, $this->expandedGroups)) {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$groupKey]));
        } else {
            $this->expandedGroups[] = $groupKey;
        }

        $this->loadData();
    }

    protected function getGroupKey(mixed $value): string
    {
        if (is_null($value)) {
            return '__null__';
        }

        if (is_bool($value)) {
            return $value ? '__true__' : '__false__';
        }

        return (string) $value;
    }

    protected function getGroupLabel(mixed $value): string
    {
        $colLabel = $this->colLabels[$this->groupBy] ?? $this->groupBy;

        if (is_null($value)) {
            return $colLabel . ': ' . __('(empty)');
        }

        if (is_bool($value)) {
            return $colLabel . ': ' . ($value ? __('Yes') : __('No'));
        }

        if (isset($this->filterValueLists[$this->groupBy])) {
            $label = collect($this->filterValueLists[$this->groupBy])
                ->firstWhere('value', $value);

            if ($label) {
                return $colLabel . ': ' . ($label['label'] ?? $value);
            }
        }

        return $colLabel . ': ' . $value;
    }

    protected function isGrouped(): bool
    {
        return ! empty($this->groupBy);
    }

    protected function loadGroupedData(Builder $query): array
    {
        $groupColumn = $this->groupBy;

        $groupQuery = $query->clone();
        $groupQuery->getQuery()->columns = null;
        $groupQuery->reorder()
            ->select($groupColumn)
            ->selectRaw('COUNT(*) as group_count')
            ->groupBy($groupColumn);

        $groupQuery->orderBy($groupColumn);

        $totalGroups = $groupQuery->clone()->getQuery()->getCountForPagination();

        $offset = ($this->currentGroupsPage - 1) * $this->groupsPerPage;
        $groupData = $groupQuery->skip($offset)->take($this->groupsPerPage)->get();

        $groups = [];

        foreach ($groupData as $groupRow) {
            $value = $groupRow->{$groupColumn};
            $groupKey = $this->getGroupKey($value);
            $isExpanded = in_array($groupKey, $this->expandedGroups);

            $groupInfo = [
                'key' => $groupKey,
                'value' => $value,
                'label' => $this->getGroupLabel($value),
                'count' => $groupRow->group_count,
                'aggregates' => [],
                'data' => [],
                'pagination' => null,
                'expanded' => $isExpanded,
            ];

            if (! empty($this->aggregatableCols)) {
                $groupInfo['aggregates'] = $this->getAggregate($query->clone()->where($groupColumn, $value));
            }

            if ($isExpanded) {
                $groupQuery = $query->clone()->where($groupColumn, $value);
                $page = $this->groupPages[$groupKey] ?? 1;
                $paginator = $groupQuery->paginate($this->groupPerPage, ['*'], 'page', $page);

                $items = $paginator->getCollection()->map(fn ($item) => $this->itemToArray($item));
                $paginator->setCollection($items);

                $groupInfo['data'] = $paginator->items();
                $groupInfo['pagination'] = [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ];
            }

            $groups[] = $groupInfo;
        }

        return [
            'groups' => $groups,
            'groups_pagination' => [
                'current_page' => $this->currentGroupsPage,
                'last_page' => (int) ceil($totalGroups / $this->groupsPerPage),
                'per_page' => $this->groupsPerPage,
                'total' => $totalGroups,
            ],
        ];
    }
}
