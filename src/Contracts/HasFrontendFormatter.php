<?php

namespace TeamNiftyGmbH\DataTable\Contracts;

interface HasFrontendFormatter
{
    /**
     * This should return the name of the frontend formatter to use.
     * See javascript formatters in resources/js/formatters for available formatters.
     *
     * @param null ...$args
     * @return string|array
     */
    public static function getFrontendFormatter(mixed ...$args): string|array;
}
