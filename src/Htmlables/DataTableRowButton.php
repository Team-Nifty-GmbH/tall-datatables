<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use WireUi\View\Components\Button;
use WireUi\View\Components\CircleButton;

class DataTableRowButton implements Htmlable
{
    /**
     * @param bool $rounded
     * @param bool $squared
     * @param bool $outline
     * @param bool $flat
     * @param bool $full
     * @param bool $circle
     * @param string|null $color
     * @param string|null $size
     * @param string|null $label
     * @param string|null $icon
     * @param string|null $rightIcon
     * @param string|null $spinner
     * @param string|null $loadingDelay
     * @param string|null $href
     * @param array|null $attributes
     * @return static
     */
    public static function make(
        bool $rounded = false,
        bool $squared = false,
        bool $outline = false,
        bool $flat = false,
        bool $full = false,
        bool $circle = false,
        ?string $color = null,
        ?string $size = null,
        ?string $label = null,
        ?string $icon = null,
        ?string $rightIcon = null,
        ?string $spinner = null,
        ?string $loadingDelay = null,
        ?string $href = null,
        ?array $attributes = []
    ): self {
        return new self(rounded: $rounded,
            squared: $squared,
            outline: $outline,
            flat: $flat,
            full: $full,
            circle: $circle,
            color: $color,
            size: $size,
            label: $label,
            icon: $icon,
            rightIcon: $rightIcon,
            spinner: $spinner,
            loadingDelay: $loadingDelay,
            href: $href,
            attributes: $attributes,
        );
    }

    /**
     * @param bool $rounded
     * @param bool $squared
     * @param bool $outline
     * @param bool $flat
     * @param bool $full
     * @param bool $circle
     * @param string|null $color
     * @param string|null $size
     * @param string|null $label
     * @param string|null $icon
     * @param string|null $rightIcon
     * @param string|null $spinner
     * @param string|null $loadingDelay
     * @param string|null $href
     * @param array|null $attributes
     */
    public function __construct(
        public bool $rounded = false,
        public bool $squared = false,
        public bool $outline = false,
        public bool $flat = false,
        public bool $full = false,
        public bool $circle = false,
        public ?string $color = null,
        public ?string $size = null,
        public ?string $label = null,
        public ?string $icon = null,
        public ?string $rightIcon = null,
        public ?string $spinner = null,
        public ?string $loadingDelay = null,
        public ?string $href = null,
        public ?array $attributes = []
    ) {
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml(): string
    {
        $buttonClass = $this->circle ? CircleButton::class : Button::class;

        $button = new $buttonClass(
            rounded: $this->rounded,
            squared: $this->squared,
            outline: $this->outline,
            flat: $this->flat,
            full: $this->full,
            color: $this->color,
            size: $this->size,
            label: $this->label,
            icon: $this->icon,
            rightIcon: $this->rightIcon,
            spinner: $this->spinner,
            loadingDelay: $this->loadingDelay,
            href: $this->href,
        );
        $button->attributes = new ComponentAttributeBag($this->attributes);

        return BladeCompiler::renderComponent($button);
    }

    public function attributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param bool $rounded
     * @return $this
     */
    public function rounded(bool $rounded = true): self
    {
        $this->rounded = $rounded;

        return $this;
    }

    /**
     * @param bool $squared
     * @return $this
     */
    public function squared(bool $squared = true): self
    {
        $this->squared = $squared;

        return $this;
    }

    /**
     * @param bool $outline
     * @return $this
     */
    public function outline(bool $outline = true): self
    {
        $this->outline = $outline;

        return $this;
    }

    /**
     * @param bool $flat
     * @return $this
     */
    public function flat(bool $flat = true): self
    {
        $this->flat = $flat;

        return $this;
    }

    /**
     * @param bool $full
     * @return $this
     */
    public function full(bool $full = true): self
    {
        $this->full = $full;

        return $this;
    }

    /**
     * @param bool $circle
     * @return $this
     */
    public function circle(bool $circle = true): self
    {
        $this->circle = $circle;

        return $this;
    }

    /**
     * @param string|null $color
     * @return $this
     */
    public function color(string $color = null): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @param string|null $size
     * @return $this
     */
    public function size(string $size = null): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param string|null $label
     * @return $this
     */
    public function label(string $label = null): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param string|null $icon
     * @return $this
     */
    public function icon(string $icon = null): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param string $rightIcon
     * @return $this
     */
    public function rightIcon(string $rightIcon): self
    {
        $this->rightIcon = $rightIcon;

        return $this;
    }

    /**
     * @param string $spinner
     * @return $this
     */
    public function spinner(string $spinner): self
    {
        $this->spinner = $spinner;

        return $this;
    }

    /**
     * @param string $loadingDelay
     * @return $this
     */
    public function loadingDelay(string $loadingDelay): self
    {
        $this->loadingDelay = $loadingDelay;

        return $this;
    }

    /**
     * @param string $href
     * @return $this
     */
    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }
}
