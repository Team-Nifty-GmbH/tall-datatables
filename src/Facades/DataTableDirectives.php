<?php

namespace TeamNiftyGmbH\DataTable\Facades;

use Illuminate\Support\Facades\Facade;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;

/**
 * @method static string scripts(bool $absolute = true, array $attributes = [])
 * @method static string styles(bool $absolute = true)
 * @method static string|null getManifestVersion(string $file, ?string &$route = null)
 * @method static string confirmAction(string $expression)
 * @method static string notify(string $expression)
 * @method static string boolean(string $value)
 * @method static string entangleable(string $property, mixed $value = null)
 */
class DataTableDirectives extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataTableBladeDirectives::class;
    }
}
