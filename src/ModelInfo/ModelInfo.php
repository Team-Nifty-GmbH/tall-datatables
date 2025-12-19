<?php

namespace TeamNiftyGmbH\DataTable\ModelInfo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Throwable;

class ModelInfo implements Arrayable
{
    private static ?array $cachedModelInfos = null;

    public array $implements = [];

    public ?string $morphClass = null;

    public function __construct(
        public string $class,
        public string $fileName,
        public string $connectionName,
        public string $tableName,
        public Collection $relations,
        public Collection $attributes,
        public Collection $traits,
        public mixed $extra = null,
    ) {}

    /**
     * Get ModelInfo for all models from the morph map.
     *
     * @return Collection<int, ModelInfo>
     */
    public static function forAllModels(
        ?string $directory = null,
        ?string $basePath = null,
        ?string $baseNamespace = null
    ): Collection {
        return ModelFinder::all($directory, $basePath, $baseNamespace)
            ->map(function (string $model) {
                return static::forModel($model);
            });
    }

    /**
     * Get ModelInfo for all models from the morph map.
     *
     * @return Collection<int, ModelInfo>
     */
    public static function fromMorphMap(): Collection
    {
        return ModelFinder::fromMorphMap()
            ->map(function (string $model) {
                return static::forModel($model);
            });
    }

    /**
     * @param  class-string<Model>|Model|ReflectionClass  $model
     */
    public static function forModel(string|Model|ReflectionClass $model): self
    {
        if (is_null(static::$cachedModelInfos)) {
            static::$cachedModelInfos = Cache::get(config('tall-datatables.cache_key') . '.modelInfo')
                ?? [];
        }

        if ($model instanceof ReflectionClass) {
            $model = $model->getName();
        }

        $cacheKey = is_string($model) ? $model : get_class($model);
        if (array_key_exists($cacheKey, static::$cachedModelInfos)) {
            return static::$cachedModelInfos[$cacheKey];
        }

        if (is_string($model)) {
            $model = app($model);
        }

        try {
            $relations = RelationFinder::forModel($model);
        } catch (Throwable $e) {
            throw $e;
        }

        try {
            $attributes = AttributeFinder::forModel($model);
        } catch (Throwable) {
            $attributes = collect();
        }

        try {
            $morphClass = $model->getMorphClass();
        } catch (ClassMorphViolationException) {
            $morphClass = $model::class;
        }

        $modelInfo = new static(
            $model::class,
            (new ReflectionClass($model))->getFileName(),
            $model->getConnection()->getName(),
            $model->getConnection()->getTablePrefix() . $model->getTable(),
            $relations,
            $attributes,
            static::getTraits($model),
            static::getExtraModelInfo($model),
        );

        $modelInfo->morphClass = $morphClass;

        $modelInfo->implements = class_implements($model) ?: [];

        $modelInfo->attributes = $modelInfo
            ->attributes
            ->map(function (Attribute $attribute) use ($model) {
                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            });

        static::$cachedModelInfos[get_class($model)] = $modelInfo;
        Cache::forever(config('tall-datatables.cache_key') . '.modelInfo', static::$cachedModelInfos);

        return $modelInfo;
    }

    protected static function getExtraModelInfo(Model $model): mixed
    {
        if (method_exists($model, 'extraModelInfo')) {
            return $model->extraModelInfo();
        }

        return null;
    }

    protected static function getTraits(Model $model): Collection
    {
        return collect(class_uses_recursive($model));
    }

    public function toArray(): array
    {
        $properties = get_object_vars($this);
        $properties['relations'] = $properties['relations']->toArray();
        $properties['attributes'] = $properties['attributes']->toArray();

        return $properties;
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

    /**
     * Clear the cached model info.
     */
    public static function clearCache(): void
    {
        static::$cachedModelInfos = null;
        Cache::forget(config('tall-datatables.cache_key') . '.modelInfo');
    }
}
