<?php

namespace TeamNiftyGmbH\DataTable\Casts\Links;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class Link implements CastsAttributes, HasFrontendFormatter
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        if ($model->hasAttributeMutator($key) || $model->hasGetMutator($key)) {
            return $model->getAttributeValue($key);
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }

    /**
     * This should return the name of the frontend formatter to use.
     * See javascript formatters in resources/js/formatters for available formatters.
     *
     * @param null ...$args
     * @return string|array
     */
    public static function getFrontendFormatter(...$args): string|array
    {
        return 'link';
    }
}
