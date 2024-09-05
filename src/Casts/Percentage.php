<?php

namespace TeamNiftyGmbH\DataTable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class Percentage implements CastsAttributes, HasFrontendFormatter
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($model->hasAttributeMutator($key) || $model->hasGetMutator($key)) {
            return $model->getAttributeValue($key);
        }

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    /**
     * This should return the name of the frontend formatter to use.
     * See javascript formatters in resources/js/formatters for available formatters.
     *
     * @param  null  ...$args
     */
    public static function getFrontendFormatter(...$args): string|array
    {
        return 'percentage';
    }
}
