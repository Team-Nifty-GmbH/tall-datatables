<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\View\Compilers\ComponentTagCompiler;
use TeamNiftyGmbH\DataTable\Facades\DataTableDirectives;

class DataTableTagCompiler extends ComponentTagCompiler
{
    /**
     * @return string
     */
    public function compile(string $value)
    {
        return $this->compileDataTableSelfClosingTags($value);
    }

    /**
     * @return string
     */
    protected function compileDataTableSelfClosingTags(string $value)
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
