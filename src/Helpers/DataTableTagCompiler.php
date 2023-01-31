<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\View\Compilers\ComponentTagCompiler;
use TeamNiftyGmbH\DataTable\Facades\DataTableDirectives;

class DataTableTagCompiler extends ComponentTagCompiler
{
    public function compile($value)
    {
        return $this->compileDataTableSelfClosingTags($value);
    }

    protected function compileDataTableSelfClosingTags($value)
    {
        $pattern = '/<\s*datatable\:(scripts|styles)\s*\/?>/';

        return preg_replace_callback($pattern, function (array $matches) {
            $element = '<script>throw new Error("Wrong <datatable:scripts /> usage. It should be <datatable:scripts />")</script>';

            if ($matches[1] === 'scripts') {
                $element = DataTableDirectives::scripts();
            }

            if ($matches[1] === 'styles') {
                $element = DataTableDirectives::styles();
            }

            return $element;
        }, $value);
    }
}
