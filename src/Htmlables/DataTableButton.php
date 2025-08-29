<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use TallStackUi\View\Components\Button\Button;
use TallStackUi\View\Components\Button\Circle;

class DataTableButton implements Htmlable
{
    protected bool $shouldRender = true;

    public function __construct(
        public bool $round = false,
        public bool $square = false,
        public bool $outline = false,
        public bool $flat = false,
        public bool $circle = false,
        public ?string $color = null,
        public ?string $size = null,
        public ?string $text = null,
        public ?string $icon = null,
        public ?string $position = null,
        public ?string $loading = null,
        public ?string $delay = null,
        public ?string $href = null,
        public ?bool $light = null,
        public ?array $attributes = []
    ) {}

    public static function make(
        bool $round = false,
        bool $square = false,
        bool $outline = false,
        bool $flat = false,
        bool $circle = false,
        ?string $color = null,
        ?string $size = null,
        ?string $text = null,
        ?string $icon = null,
        ?string $position = null,
        ?string $loading = null,
        ?string $delay = null,
        ?string $href = null,
        ?bool $light = null,
        ?array $attributes = []
    ): static {
        return new static(round: $round,
            square: $square,
            outline: $outline,
            flat: $flat,
            circle: $circle,
            color: $color,
            size: $size,
            text: $text,
            icon: $icon,
            position: $position,
            loading: $loading,
            delay: $delay,
            href: $href,
            light: $light,
            attributes: $attributes,
        );
    }

    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return $this
     */
    public function circle(bool $circle = true): static
    {
        $this->circle = $circle;

        return $this;
    }

    public function class(string|array $class): static
    {
        $this->attributes['class'] = is_string($class) ? $class : Arr::toCssClasses($class);

        return $this;
    }

    /**
     * @return $this
     */
    public function color(?string $color = null): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return $this
     */
    public function delay(string $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return $this
     */
    public function flat(bool $flat = true): static
    {
        $this->flat = $flat;

        return $this;
    }

    /**
     * @return $this
     */
    public function full(bool $full = true): static
    {
        $this->full = $full;

        return $this;
    }

    /**
     * @return $this
     */
    public function href(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    /**
     * @return $this
     */
    public function icon(?string $icon = null): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return $this
     */
    public function light(bool $light = true): static
    {
        $this->light = $light;

        return $this;
    }

    /**
     * @return $this
     */
    public function loading(string $loading): static
    {
        $this->loading = $loading;

        return $this;
    }

    public function mergeAttributes(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function mergeClass(string|array $class): static
    {
        $this->attributes['class'] = array_merge(
            explode(' ', $this->attributes['class'] ?? ''),
            is_string($class) ? [$class] : Arr::fromCssClasses($class)
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function outline(bool $outline = true): static
    {
        $this->outline = $outline;

        return $this;
    }

    /**
     * @return $this
     */
    public function position(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return $this
     */
    public function round(bool $round = true): static
    {
        $this->round = $round;

        return $this;
    }

    /**
     * @return $this
     */
    public function size(?string $size = null): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return $this
     */
    public function square(bool $square = true): static
    {
        $this->square = $square;

        return $this;
    }

    /**
     * @return $this
     */
    public function text(?string $text = null): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get content as a string of HTML.
     */
    public function toHtml(): string
    {
        if (! $this->shouldRender) {
            return '';
        }

        if ($this->circle) {
            $buttonClass = Circle::class;
            $this->icon = is_null($this->icon) ? 'pencil' : $this->icon;
        } else {
            $this->text = is_null($this->text) ? '' : $this->text;
            $buttonClass = Button::class;
        }

        $button = new $buttonClass(
            text: $this->text ?? null,
            icon: $this->icon,
            position: $this->position ?? 'left',
            color: $this->color ?? 'secondary',
            square: $this->square,
            round: $this->round,
            href: $this->href,
            loading: $this->loading,
            delay: $this->delay,
            outline: $this->outline,
            flat: $this->flat,
            size: $this->size ?? 'md',
            light: $this->light ?? false,
        );
        $button->attributes = new ComponentAttributeBag($this->attributes);

        return BladeCompiler::renderComponent($button);
    }

    /**
     * Render a button only if the closure is true
     */
    public function when(Closure|bool $condition): static
    {
        $this->shouldRender = (bool) value($condition);

        return $this;
    }

    public function wireClick(string $js): static
    {
        $this->attributes['wire:click'] = $js;

        return $this;
    }

    public function xOnClick(string $js): static
    {
        $this->attributes['x-on:click'] = $js;

        return $this;
    }
}
