<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Exception;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

trait HasFrontendAttributes
{
    public array $detailRouteParams = [];

    /**
     * @throws Exception
     */
    public static function icon(): Icon
    {
        $iconName = property_exists(self::class, 'iconName') ? self::$iconName : 'no-symbol';

        return Icon::make($iconName);
    }

    public static function typeScriptAttributes(): array
    {
        return ModelInfo::forModel(self::class)
            ->attributes
            ->pluck('formatter', 'name')
            ->toArray();
    }

    public function detailRoute(bool $absolute = true): ?string
    {
        return $this->getDetailRouteName()
            ? route($this->getDetailRouteName(), $this->getDetailRouteParams(), $absolute)
            : null;
    }

    public function getDetailRouteParams(): array
    {
        return array_merge(
            $this->detailRouteParams,
            method_exists($this, 'detailRouteParams') ? $this->detailRouteParams() : ['id' => $this->getKey()]
        );
    }

    /**
     * @return $this
     */
    public function setDetailRouteParams(array $routeParams): self
    {
        $this->detailRouteParams = $routeParams;

        return $this;
    }

    private function getDetailRouteName(): ?string
    {
        return $this->detailRouteName ?? null;
    }
}
