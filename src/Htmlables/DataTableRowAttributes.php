<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\View\ComponentAttributeBag;

class DataTableRowAttributes extends ComponentAttributeBag
{
    /**
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function bind(string $key, string $value): self
    {
        $this->attributes['x-bind:' . $key] = $value;

        return $this;
    }

    /**
     * @param string $event
     * @param string $callback
     * @return $this
     */
    public function on(string $event, string $callback): self
    {
        $this->attributes['x-on:' . $event] = $callback;

        return $this;
    }
}
