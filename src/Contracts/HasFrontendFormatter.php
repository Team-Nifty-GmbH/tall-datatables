<?php

namespace TeamNiftyGmbH\DataTable\Contracts;

interface HasFrontendFormatter
{
    /**
     * This should return the name of the frontend formatter to use.
     * See javascript formatters in resources/js/tall-datatables.js for available formatters.
     *
     * @param  null  ...$args
     */
    public static function getFrontendFormatter(mixed ...$args): string|array;
}
