<?php

namespace TeamNiftyGmbH\DataTable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class Money implements CastsAttributes, HasFrontendFormatter
{
    /**
     * This should return the name of the frontend formatter to use.
     * See javascript formatters in resources/js/formatters for available formatters.
     *
     * @param  null  ...$args
     */
    public static function getFrontendFormatter(...$args): string|array
    {
        return 'money';
    }

    /**
     * Cast the given value.
     */
    public function get(mixed $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($model->hasAttributeMutator($key) || $model->hasGetMutator($key)) {
            return $model->getAttributeValue($key);
        }

        $value = is_numeric($value) ? (string) $value : '0';

        return bccomp(bcmod($value, '1', 10), '0', 10) === 0
            ? round((float) $value, 2)
            : (float) $value;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(mixed $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
