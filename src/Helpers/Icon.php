<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\View\ComponentAttributeBag;

class Icon implements Htmlable, Responsable
{
    public string $name;

    public ?string $variant;

    public string $view;

    public array|ComponentAttributeBag $attributes;

    /**
     * @throws \Exception
     */
    public static function make(
        string $name,
        string $variant = 'solid',
        array|ComponentAttributeBag $attributes = []
    ): self {
        return new self($name, $variant, $attributes);
    }

    /**
     * @throws \Exception
     */
    public function __construct(
        string $name,
        string $variant = 'solid',
        array|ComponentAttributeBag $attributes = []
    ) {
        $this->name = strtolower($name);
        $this->variant = strtolower($variant);
        $this->attributes = $attributes;

        View::exists($this->getComponentName())
            || throw new \Exception('Icon not found: ' . $this->getComponentName());
    }

    /**
     * @throws \Exception
     */
    public function __toString(): string
    {
        return $this->getSvg();
    }

    /**
     * @throws \Exception
     */
    public function getSvg(): string
    {
        return $this->getView()->render();
    }

    public function getUrl(): string
    {
        return route('icons', ['name' => $this->name, 'variant' => $this->variant]);
    }

    /**
     * @throws \Exception
     */
    public function getView(): Factory|\Illuminate\Contracts\View\View|Application
    {
        $view = $this->getComponentName();

        return view($view, ['attributes' => '']);
    }

    private function getComponentName(): string
    {
        return 'heroicons::components.' . ($this->variant ?? 'solid') . '.' . $this->name;
    }

    /**
     * Get content as a string of HTML.
     *
     *
     * @throws \Exception
     */
    public function toHtml(): string
    {
        return $this->getSvg();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     *
     * @throws \Exception
     */
    public function toResponse($request): Response
    {
        View::exists($this->getComponentName()) || abort(404);

        return response($this->getView())
            ->withHeaders([
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Cache-Control' => 'public, only-if-cached, max-age=31536000',
            ]);
    }
}
