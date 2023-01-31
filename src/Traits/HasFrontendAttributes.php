<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use TeamNiftyGmbH\DataTable\Casts\Links\Image;
use TeamNiftyGmbH\DataTable\Casts\Links\Link;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

trait HasFrontendAttributes
{
    public array $detailRouteParams = [];

    /**
     * @param array $routeParams
     * @return $this
     */
    public function setDetailRouteParams(array $routeParams): self
    {
        $this->detailRouteParams = $routeParams;

        return $this;
    }

    /**
     * @param bool $absolute
     * @return string|null
     */
    public function detailRoute(bool $absolute = true): string|null
    {
        return $this->getDetailRouteName()
            ? route($this->getDetailRouteName(), $this->getDetailRouteParams(), $absolute)
            : null;
    }

    /**
     * @return array
     */
    public function getDetailRouteParams(): array
    {
        return array_merge(
            $this->detailRouteParams,
            method_exists($this, 'detailRouteParams') ? $this->detailRouteParams() : ['id' => $this->getKey()]
        );
    }

    /**
     * @return string|null
     */
    public function getHrefAttribute(): ?string
    {
        return $this->detailRoute();
    }

    /**
     * @return string|null
     */
    private function getDetailRouteName(): string|null
    {
        return $this->detailRouteName ?? null;
    }

    /**
     * @return array
     */
    public static function typeScriptAttributes(): array
    {
        return ModelInfo::forModel(self::class)
            ->attributes
            ->pluck('formatter', 'name')
            ->toArray();
    }

    /**
     * @return Icon
     *
     * @throws \Exception
     */
    public static function icon(): Icon
    {
        $iconName = property_exists(self::class, 'iconName') ? self::$iconName : 'no-symbol';

        return Icon::make($iconName);
    }
}
