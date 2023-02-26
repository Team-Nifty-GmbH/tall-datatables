<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\View\ComponentAttributeBag;

class DataTableRowAttributes extends ComponentAttributeBag
{
    public static function make(): self
    {
        return new self();
    }

    /**
     * @return $this
     */
    public function bind(string $key, string $value): self
    {
        $this->attributes['x-bind:' . $key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function on(string $event, string $callback): self
    {
        $this->attributes['x-on:' . $event] = $callback;

        return $this;
    }
}
