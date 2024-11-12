<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\View\ComponentAttributeBag;

class DataTableRowAttributes extends ComponentAttributeBag
{
    public static function make(): static
    {
        return new static;
    }

    /**
     * @return $this
     */
    public function bind(string $key, string $value): static
    {
        $this->attributes['x-bind:' . $key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function on(string $event, string $callback): static
    {
        $this->attributes['x-on:' . $event] = $callback;

        return $this;
    }
}
