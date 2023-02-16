<?php

namespace TeamNiftyGmbH\DataTable\Htmlables;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use WireUi\View\Components\Button;
use WireUi\View\Components\CircleButton;

class DataTableButton implements Htmlable
{
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
     */
    public function toHtml(): string
    {
        if ($this->circle) {
            $buttonClass = CircleButton::class;
            $this->icon = is_null($this->icon) ? 'pencil' : $this->icon;
        } else {
            $buttonClass = Button::class;
            $this->label = is_null($this->label) ? '' : $this->label;
        }

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
     * @return $this
     */
    public function rounded(bool $rounded = true): self
    {
        $this->rounded = $rounded;

        return $this;
    }

    /**
     * @return $this
     */
    public function squared(bool $squared = true): self
    {
        $this->squared = $squared;

        return $this;
    }

    /**
     * @return $this
     */
    public function outline(bool $outline = true): self
    {
        $this->outline = $outline;

        return $this;
    }

    /**
     * @return $this
     */
    public function flat(bool $flat = true): self
    {
        $this->flat = $flat;

        return $this;
    }

    /**
     * @return $this
     */
    public function full(bool $full = true): self
    {
        $this->full = $full;

        return $this;
    }

    /**
     * @return $this
     */
    public function circle(bool $circle = true): self
    {
        $this->circle = $circle;

        return $this;
    }

    /**
     * @return $this
     */
    public function color(string $color = null): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return $this
     */
    public function size(string $size = null): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return $this
     */
    public function label(string $label = null): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return $this
     */
    public function icon(string $icon = null): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return $this
     */
    public function rightIcon(string $rightIcon): self
    {
        $this->rightIcon = $rightIcon;

        return $this;
    }

    /**
     * @return $this
     */
    public function spinner(string $spinner): self
    {
        $this->spinner = $spinner;

        return $this;
    }

    /**
     * @return $this
     */
    public function loadingDelay(string $loadingDelay): self
    {
        $this->loadingDelay = $loadingDelay;

        return $this;
    }

    /**
     * @return $this
     */
    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }
}
