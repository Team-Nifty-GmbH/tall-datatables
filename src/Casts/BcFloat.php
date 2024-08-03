<?php

namespace TeamNiftyGmbH\DataTable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Number;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class BcFloat implements CastsAttributes, HasFrontendFormatter
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        if ($model->hasAttributeMutator($key) || $model->hasGetMutator($key)) {
            return $model->getAttributeValue($key);
        }

        $value = Number::trim($value ?? 0);

        return fmod($value, 1) === 0.0
            // not a decimal number, pad with 2 decimal places
            ? (float) number_format($value, 2, '.', '')
            // a decimal number, return as is
            : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     */
    public function set($model, string $key, $value, array $attributes): mixed
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
        return 'float';
    }
}
