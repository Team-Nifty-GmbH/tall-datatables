<?php

namespace TeamNiftyGmbH\DataTable\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TeamNiftyGmbH\DataTable\Helpers\SchemaInfo;

class ColumnResolver
{
    public function __construct(protected string|Model $model) {}

    /**
     * Discover all non-virtual, non-appended columns for the model.
     *
     * @return array<string, array{name: string, cast: ?string, type: ?string}>
     */
    public function getColumns(): array
    {
        $modelInfo = SchemaInfo::forModel($this->model);

        $columns = [];
        foreach ($modelInfo->attributes as $attribute) {
            if ($attribute->virtual || $attribute->appended) {
                continue;
            }

            $columns[$attribute->name] = [
                'name' => $attribute->name,
                'cast' => $attribute->cast,
                'type' => $attribute->type,
            ];
        }

        return $columns;
    }

    /**
     * Determine the filter input type for a column.
     *
     * For dot-notation columns, resolves casts from the related model.
     */
    public function getInputType(string $column): string
    {
        $casts = $this->resolveCasts($column);
        $base = str_contains($column, '.') ? last(explode('.', $column)) : $column;

        if (! array_key_exists($base, $casts)) {
            // Fall back to column type info from model info
            return $this->inputTypeFromModelInfo($column);
        }

        $castValue = $casts[$base];
        $castType = str_contains($castValue, ':')
            ? substr($castValue, 0, strpos($castValue, ':'))
            : $castValue;

        return $this->mapCastToInputType($castType);
    }

    /**
     * Return a translated label for a column. Falls back to Str::headline().
     */
    public function getLabel(string $column): string
    {
        // For dot-notation, use the last segment
        $base = str_contains($column, '.') ? last(explode('.', $column)) : $column;

        $translated = __($base);

        // If the translation key was not found, the result equals the key
        if ($translated === $base) {
            return Str::headline($base);
        }

        return $translated;
    }

    private function inputTypeFromModelInfo(string $column): string
    {
        $base = str_contains($column, '.') ? last(explode('.', $column)) : $column;

        $modelClass = $this->resolveModelClass($column);
        if ($modelClass === null) {
            return 'text';
        }

        $modelInfo = SchemaInfo::forModel($modelClass);
        $attribute = $modelInfo->attributes->firstWhere('name', $base);

        if ($attribute === null) {
            return 'text';
        }

        $dbType = strtolower($attribute->type ?? '');

        return match (true) {
            str_contains($dbType, 'int') => 'number',
            in_array($dbType, ['decimal', 'float', 'double', 'numeric', 'real']) => 'number',
            in_array($dbType, ['boolean', 'bool', 'tinyint(1)']) => 'boolean',
            in_array($dbType, ['date', 'datetime', 'timestamp']) => 'datetime',
            default => 'text',
        };
    }

    private function mapCastToInputType(string $castType): string
    {
        return match (strtolower($castType)) {
            'integer', 'int', 'decimal', 'float', 'double' => 'number',
            'boolean', 'bool' => 'boolean',
            'date', 'immutable_date' => 'datetime',
            'datetime', 'immutable_datetime', 'timestamp' => 'datetime',
            default => 'text',
        };
    }

    /**
     * Resolve casts for the model owning the given column (supports dot-notation).
     *
     * @return array<string, string>
     */
    private function resolveCasts(string $column): array
    {
        $modelClass = $this->resolveModelClass($column);

        if ($modelClass === null) {
            return [];
        }

        return (new $modelClass())->getCasts();
    }

    /**
     * Resolve which model class owns the column.
     * For dot-notation, traverses relations to find the related model.
     */
    private function resolveModelClass(string $column): ?string
    {
        if (! str_contains($column, '.')) {
            return is_string($this->model) ? $this->model : get_class($this->model);
        }

        $parts = explode('.', $column);
        array_pop($parts); // remove the field name

        $modelClass = is_string($this->model) ? $this->model : get_class($this->model);

        foreach ($parts as $relationName) {
            $modelInfo = SchemaInfo::forModel($modelClass);
            $relation = $modelInfo->relation($relationName);

            if ($relation === null) {
                return null;
            }

            $modelClass = $relation->related;
        }

        return $modelClass;
    }
}
