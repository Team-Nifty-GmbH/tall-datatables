<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

class DataTableRowAttributes extends ComponentAttributeBag implements Htmlable
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
}
