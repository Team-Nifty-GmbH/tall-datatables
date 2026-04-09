<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Exception;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Helpers\SchemaInfo;

trait HasFrontendAttributes
{
    public array $detailRouteParams = [];

    /**
     * @throws Exception
     */
    public static function icon(): Icon
    {
        $iconName = property_exists(static::class, 'iconName') ? static::$iconName : 'no-symbol';

        return Icon::make($iconName);
    }

    public static function typeScriptAttributes(): array
    {
        return SchemaInfo::forModel(static::class)
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
    public function setDetailRouteParams(array $routeParams): static
    {
        $this->detailRouteParams = $routeParams;

        return $this;
    }

    protected function getDetailRouteName(): ?string
    {
        return $this->detailRouteName ?? null;
    }
}
