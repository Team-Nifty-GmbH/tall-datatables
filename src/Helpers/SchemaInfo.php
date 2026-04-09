<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use TeamNiftyGmbH\DataTable\DataTransferObjects\Relation;
use TeamNiftyGmbH\DataTable\ModelInfo\Attribute;
use UnitEnum;

class SchemaInfo
{
    /** @var array<string, static> */
    protected static array $cache = [];

    public function __construct(
        public string $class,
        public string $tableName,
        public ?string $morphClass,
        public array $implements,
        public Collection $attributes,
        public Collection $relations,
    ) {}

    public static function flush(): void
    {
        static::$cache = [];

        $cacheKey = config('tall-datatables.cache_key');
        Cache::forget($cacheKey . '.modelInfo');
        Cache::forget($cacheKey . '.modelFinder');
        Cache::forget($cacheKey . '.with');
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    public static function forModel(string|Model $model): static
    {
        $cacheKey = is_string($model) ? $model : get_class($model);

        if (array_key_exists($cacheKey, static::$cache)) {
            return static::$cache[$cacheKey];
        }

        if (is_string($model)) {
            $model = app($model);
        }

        $cacheKey = get_class($model);

        if (array_key_exists($cacheKey, static::$cache)) {
            return static::$cache[$cacheKey];
        }

        $relations = RelationFinder::forModel($model);

        try {
            $morphClass = $model->getMorphClass();
        } catch (ClassMorphViolationException) {
            $morphClass = $model::class;
        }

        $table = $model->getTable();
        $connection = $model->getConnection();
        $schema = $connection->getSchemaBuilder();

        $columns = $schema->getColumns($table);
        $indexes = $schema->getIndexes($table);

        $attributes = static::buildAttributes($model, $columns, $indexes);

        $info = new static(
            class: $model::class,
            tableName: $connection->getTablePrefix() . $table,
            morphClass: $morphClass,
            implements: class_implements($model) ?: [],
            attributes: $attributes,
            relations: $relations,
        );

        static::$cache[$cacheKey] = $info;

        return $info;
    }

    protected static function attributeIsHidden(string $attribute, Model $model): bool
    {
        $hidden = $model->getHidden();

        if (count($hidden) > 0) {
            return in_array($attribute, $hidden);
        }

        $visible = $model->getVisible();

        if (count($visible) > 0) {
            return ! in_array($attribute, $visible);
        }

        return false;
    }

    /**
     * @return Collection<int, Attribute>
     */
    protected static function buildAttributes(Model $model, array $columns, array $indexes): Collection
    {
        $dbAttributes = collect($columns)
            ->values()
            ->map(function (array $column) use ($model, $indexes) {
                $columnIndexes = static::getIndexesForColumn($column['name'], $indexes);
                $cast = static::getCastType($column['name'], $model);

                $attribute = new Attribute(
                    name: $column['name'],
                    phpType: static::getPhpType($cast, $column),
                    type: $column['type'],
                    increments: $column['auto_increment'],
                    nullable: $column['nullable'],
                    default: static::getColumnDefault($column, $model),
                    primary: $columnIndexes->contains(fn (array $index) => $index['primary']),
                    unique: $columnIndexes->contains(fn (array $index) => $index['unique']),
                    fillable: $model->isFillable($column['name']),
                    appended: null,
                    cast: $cast,
                    virtual: false,
                    hidden: static::attributeIsHidden($column['name'], $model),
                );

                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            });

        $virtualAttributes = static::getVirtualAttributes($model, $columns);

        return $dbAttributes->merge($virtualAttributes);
    }

    protected static function getCastsWithDates(Model $model): Collection
    {
        return collect($model->getDates())
            ->whereNotNull()
            ->flip()
            ->map(fn () => 'datetime')
            ->merge($model->getCasts());
    }

    protected static function getCastType(string $column, Model $model): ?string
    {
        if ($model->hasGetMutator($column) || $model->hasSetMutator($column)) {
            return 'accessor';
        }

        if ($model->hasAttributeMutator($column)) {
            return 'attribute';
        }

        return static::getCastsWithDates($model)->get($column);
    }

    protected static function getColumnDefault(array $column, Model $model): mixed
    {
        $attributeDefault = $model->getAttributes()[$column['name']] ?? null;

        return match (true) {
            $attributeDefault instanceof BackedEnum => $attributeDefault->value,
            $attributeDefault instanceof UnitEnum => $attributeDefault->name,
            default => $attributeDefault ?? $column['default'],
        };
    }

    /**
     * @return Collection<int, array>
     */
    protected static function getIndexesForColumn(string $column, array $indexes): Collection
    {
        return collect($indexes)
            ->filter(fn (array $index) => count($index['columns']) === 1 && $index['columns'][0] === $column);
    }

    protected static function getPhpType(?string $cast, array $column): string
    {
        return static::getPhpTypeFromCast($cast) ?? static::getPhpTypeFromColumn($column);
    }

    protected static function getPhpTypeFromCast(?string $cast): ?string
    {
        if (! $cast) {
            return null;
        }

        $castFirstPart = explode(':', $cast)[0];

        $type = match ($castFirstPart) {
            'array' => 'array',
            'boolean' => 'bool',
            'float', 'decimal', 'double', 'real' => 'float',
            'integer' => 'int',
            'object' => 'object',
            'AsStringable' => '\\' . AsStringable::class,
            'collection', 'AsEnumCollection' => '\\' . Collection::class,
            'date', 'datetime', 'timestamp', 'immutable_date', 'immutable_datetime' => '\\' . CarbonInterface::class,
            default => null,
        };

        $type ??= match ($cast) {
            'encrypted:array' => 'array',
            'encrypted:collection', 'AsEncryptedCollection' => '\\' . Collection::class,
            'encrypted:object', 'AsEncryptedArrayObject' => 'object',
            default => null,
        };

        $type ??= enum_exists($cast) ? '\\' . $cast : null;

        return $type;
    }

    protected static function getPhpTypeFromColumn(array $column): string
    {
        $type = match ($column['type']) {
            'tinyint(1)', 'bit' => 'bool',
            default => null,
        };

        $type ??= match ($column['type_name']) {
            'tinyint', 'integer', 'int', 'int4', 'smallint', 'int2', 'mediumint', 'bigint', 'int8' => 'int',
            'float', 'real', 'float4', 'double', 'float8' => 'float',
            'binary', 'varbinary', 'bytea', 'image', 'blob', 'tinyblob', 'mediumblob', 'longblob' => 'resource',
            'boolean', 'bool' => 'bool',
            'date', 'time', 'timetz', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset', 'timestamp', 'timestamptz' => '\\' . CarbonInterface::class,
            'json', 'jsonb' => 'mixed',
            default => null,
        };

        return $type ?? 'string';
    }

    /**
     * @return Collection<int, Attribute>
     */
    protected static function getVirtualAttributes(Model $model, array $columns): Collection
    {
        $class = new ReflectionClass($model);
        $columnNames = collect($columns)->pluck('name');

        return collect($class->getMethods())
            ->reject(
                fn (ReflectionMethod $method) => $method->isStatic()
                    || $method->isAbstract()
                    || $method->getDeclaringClass()->getName() !== get_class($model)
            )
            ->map(function (ReflectionMethod $method) use ($model) {
                if (preg_match('/(?<=^|;)get([^;]+?)Attribute(;|$)/', $method->getName(), $matches) === 1) {
                    return [
                        'name' => Str::snake($matches[1]),
                        'cast_type' => 'accessor',
                        'php_type' => $method->getReturnType()?->getName(),
                    ];
                }

                if (preg_match('/(?<=^|;)set([^;]+?)Attribute(;|$)/', $method->getName(), $matches) === 1) {
                    return [
                        'name' => Str::snake($matches[1]),
                        'cast_type' => 'mutator',
                        'php_type' => collect($method->getParameters())->firstWhere('name', 'value')?->getType()?->__toString(),
                    ];
                }

                if ($model->hasAttributeMutator($method->getName())) {
                    return [
                        'name' => Str::snake($method->getName()),
                        'cast_type' => 'attribute',
                        'php_type' => null,
                    ];
                }

                return [];
            })
            ->reject(fn ($cast) => ! isset($cast['name']) || $columnNames->contains($cast['name']))
            ->map(function ($cast) use ($model) {
                $attribute = new Attribute(
                    name: $cast['name'],
                    phpType: $cast['php_type'] ?? null,
                    type: null,
                    increments: false,
                    nullable: null,
                    default: null,
                    primary: null,
                    unique: null,
                    fillable: $model->isFillable($cast['name']),
                    appended: $model->hasAppended($cast['name']),
                    cast: $cast['cast_type'],
                    virtual: true,
                    hidden: static::attributeIsHidden($cast['name'], $model),
                );

                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            })
            ->groupBy('name')
            ->flatMap(function (Collection $items) {
                if ($items->count() !== 2) {
                    return $items;
                }

                if (! $items->firstWhere('cast', 'accessor') || ! $items->firstWhere('cast', 'mutator')) {
                    return $items;
                }

                $attribute = $items->first();
                $attribute->phpType = $items[0]->phpType ?? $items[1]->phpType;
                $attribute->cast = 'attribute';

                return [$attribute];
            });
    }

    public function attribute(string $name): ?Attribute
    {
        return $this->attributes->first(
            fn (Attribute $attribute) => $attribute->name === $name
        );
    }

    public function relation(string $name): ?Relation
    {
        return $this->relations->first(
            fn (Relation $relation) => $relation->name === $name
        );
    }
}
