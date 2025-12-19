<?php

namespace TeamNiftyGmbH\DataTable\ModelInfo;

use Error;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

class ModelFinder
{
    /**
     * Get all models from the filesystem (with caching).
     *
     * @return Collection<int, class-string<Model>>
     */
    public static function all(
        ?string $directory = null,
        ?string $basePath = null,
        ?string $baseNamespace = null,
    ): Collection {
        $paramHash = md5(serialize(func_get_args()));

        $cached = Cache::get(config('tall-datatables.cache_key') . '.modelFinder') ?? [];

        if ($cached[$paramHash] ?? false) {
            return $cached[$paramHash];
        }

        $cached[$paramHash] = static::discover($directory, $basePath, $baseNamespace);
        Cache::put(config('tall-datatables.cache_key') . '.modelFinder', $cached);

        return $cached[$paramHash];
    }

    /**
     * Get all models from the morph map.
     *
     * @return Collection<int, class-string<Model>>
     */
    public static function fromMorphMap(): Collection
    {
        return collect(Relation::morphMap())
            ->values()
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class));
    }

    /**
     * Discover all models from the filesystem.
     *
     * @return Collection<int, class-string<Model>>
     */
    protected static function discover(
        ?string $directory = null,
        ?string $basePath = null,
        ?string $baseNamespace = null,
    ): Collection {
        $directory ??= app_path();
        $basePath ??= base_path();
        $baseNamespace ??= '';

        return collect(static::getFilesRecursively($directory))
            ->map(fn (string $class) => new SplFileInfo($class))
            ->map(fn (SplFileInfo $file) => self::fullQualifiedClassNameFromFile($file, $basePath, $baseNamespace))
            ->map(function (string $class) {
                try {
                    return new ReflectionClass($class);
                } catch (Exception|Error) {
                    return null;
                }
            })
            ->filter()
            ->filter(fn (ReflectionClass $class) => $class->isSubclassOf(Model::class))
            ->filter(fn (ReflectionClass $class) => ! $class->isAbstract())
            ->map(fn (ReflectionClass $reflectionClass) => $reflectionClass->getName())
            ->values();
    }

    protected static function fullQualifiedClassNameFromFile(
        SplFileInfo $file,
        string $basePath,
        string $baseNamespace
    ): string {
        return Str::of($file->getRealPath())
            ->replaceFirst($basePath, '')
            ->replaceLast('.php', '')
            ->trim(DIRECTORY_SEPARATOR)
            ->ucfirst()
            ->replace(
                [DIRECTORY_SEPARATOR, 'App\\'],
                ['\\', app()->getNamespace()],
            )
            ->prepend($baseNamespace . '\\');
    }

    protected static function getFilesRecursively(string $path): array
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files = [];

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            $files[] = $file->getPathname();
        }

        return $files;
    }
}
