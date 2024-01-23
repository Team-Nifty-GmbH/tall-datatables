<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use ReflectionMethod;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\Relations\Relation;
use Spatie\ModelStates\State;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

trait SupportsRelations
{
    public array $selectedRelations = [];

    public ?string $loadedPath = null;

    public array $displayPath = [];

    public array $selectedCols = [];

    #[Locked]
    public array $with = [];

    public function mountSupportsRelations(): void
    {
        $this->loadRelation($this->model, null);
    }

    #[Renderless]
    public function loadRelation(?string $model = null, ?string $relationName = null): void
    {
        if ($this->availableRelations !== ['*'] &&
            ! in_array($relationName, $this->availableRelations) &&
            ! is_null($relationName)
        ) {
            return;
        }

        $model = $model ?: $this->model;

        $this->loadedPath = $relationName ? ($this->loadedPath ? $this->loadedPath . '.' : null) . $relationName : null;

        $path = [];
        $previousPath = null;
        if ($this->loadedPath) {
            foreach (explode('.', $this->loadedPath) as $loadedSegment) {
                $path[] = [
                    'value' => $previousPath ? $previousPath . '.' . $loadedSegment : $loadedSegment,
                    'label' => __(Str::headline($loadedSegment)),
                ];

                $previousPath = $previousPath ? $previousPath . '.' . $loadedSegment : $loadedSegment;
            }
        }

        $this->displayPath = $path;

        $modelInfo = ModelInfo::forModel($model);
        $this->selectedRelations = $this->getModelRelations($modelInfo);
        if ($this->availableRelations !== ['*']) {
            $this->selectedRelations = array_intersect_key($this->selectedRelations, array_flip($this->availableRelations));
        }

        $selectedCols = $modelInfo->attributes->pluck('name')->toArray();

        if ($this->availableCols !== ['*']) {
            $selectedCols = array_intersect($selectedCols, $this->availableCols);
        }

        $this->selectedCols = array_map(function ($item) use ($modelInfo) {
            $slug = $this->loadedPath ? $this->loadedPath . '.' . $item : $item;
            $attributeInfo = $modelInfo->attribute($item);

            return [
                'label' => __(Str::headline($item)),
                'col' => $item,
                'slug' => $slug,
                'virtual' => $attributeInfo->virtual,
                'type' => $attributeInfo->type,
                'attribute' => implode('.', array_map(fn ($segment) => Str::snake($segment), explode('.', $slug))),
            ];
        }, $selectedCols);

        Cache::put(
            'relation-tree-widget.' . $this->loadedPath ?? $this->model,
            [
                'cols' => $this->selectedCols,
                'relations' => $this->selectedRelations,
                'displayPath' => $path,
            ]
        );
    }

    #[Renderless]
    public function loadSlug(?string $path = null): void
    {
        if ($this->availableRelations !== ['*'] && ! in_array($path, $this->availableRelations)) {
            return;
        }

        $this->loadedPath = $path;
        $data = Cache::get('relation-tree-widget.' . $path ?? $this->model);

        $this->selectedCols = $data['cols'];
        $this->selectedRelations = $data['relations'];
        $this->displayPath = $data['displayPath'];
    }

    #[Renderless]
    public function getFilterableColumns(?string $name = null): array
    {
        return $this->constructWith()[2];
    }

    public function getRelationTableCols(?string $relationName = null): array
    {
        $modelInfo = ModelInfo::forModel($this->model);

        if ($relationName) {
            $modelInfo = ModelInfo::forModel($modelInfo->relation($relationName)->related);
        }

        return $modelInfo
            ->attributes
            ->filter(fn ($attribute) => ! $attribute->virtual)
            ->when(
                $this->availableCols !== ['*'],
                fn ($attributes) => $attributes->whereIn('name', $this->availableCols)
            )
            ->each(fn (Attribute $attribute) => $this->getFilterValueList($relationName . '.' . $attribute->name, $attribute))
            ->pluck('formatter', 'name')
            ->toArray();
    }

    protected function constructWith(): array
    {
        // cache key for the enabled cols
        $cacheKey = md5(json_encode($this->enabledCols) . $this->getCacheKey());
        $cached = Cache::get(config('tall-datatables.cache_key') . '.with');

        if ($cached && data_get($cached, $cacheKey, false)) {
            //return $cached[$cacheKey];
        }

        $modelBase = new $this->model;
        $with = ['__root__' => []];
        $modelInfos = [];
        $filterable = [];
        $sortable = [];

        foreach ($this->enabledCols as $enabledCol) {
            $segments = explode('.', $enabledCol);
            $fieldName = array_pop($segments);

            $path = null;
            $model = null;
            $relationInstance = null;
            foreach ($segments as $segment) {
                $relationName = Str::camel($segment);
                $parentPath = $path;
                $path = $path ? $path . '.' . $relationName : $relationName;

                if ($model) {
                    $relationInstance = $model->{$relationName}();
                } else {
                    $relationInstance = $modelBase->{$relationName}();
                }
                $model = $relationInstance->getRelated();

                if ($relationInstance instanceof BelongsTo) {
                    if (method_exists($relationInstance, 'getOwnerKeyName')) {
                        $with[$path][] = $relationInstance->getOwnerKeyName();
                    }

                    if (method_exists($relationInstance, 'getForeignKeyName')) {
                        $with[$parentPath ?? '__root__'][] = $relationInstance->getForeignKeyName();
                    }
                } else {
                    if (method_exists($relationInstance, 'getOwnerKeyName')) {
                        $with[$parentPath ?? '__root__'][] = $relationInstance->getOwnerKeyName();
                    }

                    if (method_exists($relationInstance, 'getForeignKeyName')) {
                        $with[$path][] = $relationInstance->getForeignKeyName();
                    }
                }
            }

            // check if the field is virtual or has a value list to filter
            // if $model is empty, we are on the root model
            if ($modelInfos[$model ? get_class($model) : $this->model] ?? false) {
                $modelInfo = $modelInfos[$model ? get_class($model) : $this->model];
            } else {
                $modelInfo = ModelInfo::forModel($model ? get_class($model) : $this->model);
                $modelInfos[$model ? get_class($model) : $this->model] = $modelInfo;
            }

            $attributeInfo = $modelInfo->attribute($fieldName);

            if (! ($attributeInfo?->virtual ?? true)) {
                if (($with[$path ?? '__root__'] ?? false) !== ['*']) {
                    $with[$path ?? '__root__'][] = $fieldName;
                }
                $filterable[] = $enabledCol;
                $this->getFilterValueList($enabledCol, $attributeInfo);

                // only sortable if the field is not virtual
                // and the relationInstance has getForeignKeyName or getForeignKey
                if (! $segments || ($relationInstance && (method_exists($relationInstance, 'getForeignKeyName') || method_exists($relationInstance, 'getForeignKey')))) {
                    $sortable[] = $enabledCol;
                }
            } else {
                // a virtual attribute is enabled, as we dont know which other fields are required
                // for the virtual attribute we cant use the select statement
                $with[$path ?? '__root__'] = ['*'];
            }
        }

        $select = Arr::pull($with, '__root__');
        $select = array_map(fn ($field) => $modelBase->getTable() . '.' . $field, $select);
        foreach ($with as $name => $item) {
            $with[$name] = $name . ':' . implode(',', array_values(array_unique($item)));
        }

        $returnValue = [
            array_values($with),
            $select,
            $filterable,
            $this->filterValueLists ?? [],
            array_values(array_unique($sortable ?? [])),
        ];

        Cache::put(
            config('tall-datatables.cache_key') . '.with',
            array_merge($cached ?? [], [$cacheKey => $returnValue])
        );

        return $returnValue;
    }

    protected function getModelRelations($modelInfo): array
    {
        $modelQuery = new $modelInfo->class;
        $modelRelations = [];
        foreach ($modelInfo->relations as $relation) {
            $reflection = new ReflectionMethod($modelQuery, $relation->name);

            if ($reflection->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            $relationInstance = $modelQuery->{$relation->name}();

            // exclude morph relations
            if ($relationInstance instanceof MorphOneOrMany || $relationInstance instanceof MorphToMany) {
                continue;
            }

            $currentPath = $relation->name;

            data_set($modelRelations, $currentPath . '.model', $relation->related);
            data_set($modelRelations, $currentPath . '.label', __(Str::headline($relation->name)));
            data_set($modelRelations, $currentPath . '.name', $relation->name);
            data_set($modelRelations, $currentPath . '.type', $relation->type);

            if (method_exists($relationInstance, 'getOwnerKeyName')) {
                data_set($modelRelations, $currentPath . '.keys.owner', $relationInstance->getOwnerKeyName());
            }

            if (method_exists($relationInstance, 'getForeignKeyName')) {
                data_set($modelRelations, $currentPath . '.keys.foreign', $relationInstance->getForeignKeyName());
            }

            if (method_exists($relationInstance, 'getRelatedPivotKeyName')) {
                data_set($modelRelations, $currentPath . '.keys.owner', $relationInstance->getRelatedPivotKeyName());
            }

            if (method_exists($relationInstance, 'getForeignPivotKeyName')) {
                data_set($modelRelations, $currentPath . '.keys.foreign', $relationInstance->getForeignPivotKeyName());
            }
        }

        return $modelRelations;
    }

    protected function addDynamicJoin(Builder $query, string $relationPath, array $additionalSelects = []): string
    {
        $relationParts = explode('.', $relationPath);
        $model = $query->getModel();

        // Start with the columns from the main model
        $selects = [$model->getTable() . '.*'];

        foreach ($relationParts as $relationName) {
            $relationName = Str::camel($relationName);

            if (! method_exists($model, $relationName)) {
                throw new \Exception("Relation '{$relationName}' is not defined on " . get_class($model));
            }

            $relation = $model->$relationName();

            if (! $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                throw new \Exception("Method '{$relationName}' on " . get_class($model) . ' does not return a relation.');
            }

            $relatedModel = $relation->getRelated();
            $relatedTable = $relatedModel->getTable();
            $parentTable = $model->getTable();

            if (method_exists($relation, 'getForeignKeyName')) {
                // For belongsTo relationships
                $foreignKey = $relation->getForeignKeyName();
                $ownerKey = $relation->getOwnerKeyName();
                $query->join($relatedTable, "$parentTable.$foreignKey", '=', "$relatedTable.$ownerKey");
            } elseif (method_exists($relation, 'getForeignKey')) {
                // For hasOne and hasMany relationships
                $foreignKey = $relation->getForeignKey();
                $localKey = $relation->getLocalKeyName();
                $query->join($relatedTable, "$relatedTable.$foreignKey", '=', "$parentTable.$localKey");
            } else {
                throw new \Exception("Unsupported relation type for '{$relationName}' on " . get_class($model));
            }

            $selects[] = "$relatedTable.*";

            // Update the model to the next relation's model
            $model = $relatedModel;
        }

        // Add any additional selects specified by the user
        foreach ($additionalSelects as $additionalSelect) {
            $selects[] = $additionalSelect;
        }

        $query->select($selects);

        return $relatedTable;
    }

    protected function getFilterValueList(string $enabledCol, Attribute $attributeInfo): void
    {
        if ($filterValueList[$enabledCol] ?? false) {
            return;
        }

        if ($attributeInfo->type === 'boolean') {
            $this->filterValueLists[$enabledCol] = [
                [
                    'value' => 1,
                    'label' => __('Yes'),
                ],
                [
                    'value' => 0,
                    'label' => __('No'),
                ],
            ];

            return;
        }

        if (is_a($attributeInfo->cast, State::class, true)) {
            $this->filterValueLists[$enabledCol] = $attributeInfo->cast::getStateMapping()
                ->mapWithKeys(function ($state, $key) {
                    return [$key => [
                        'value' => $key,
                        'label' => __($key),
                    ]];
                })
                ->values()
                ->toArray();

            return;
        }

        if (! $attributeInfo->cast || ! class_exists($attributeInfo->cast)) {
            return;
        }

        $castReflection = new \ReflectionClass($attributeInfo->cast);

        if ($castReflection->isEnum()) {
            $this->filterValueLists[$enabledCol] = array_map(function ($enum) {
                return [
                    'value' => $enum->name,
                    'label' => __($enum->value),
                ];
            }, $attributeInfo->cast::cases());
        }
    }
}
