<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TeamNiftyGmbH\DataTable\Helpers\Icon;

class IconController
{
    /**
     * @param Request $request
     * @param string $name
     * @param string|null $variant
     * @return Response
     *
     * @throws \Exception
     */
    public function __invoke(Request $request, string $name, string $variant = null): Response
    {
        return Icon::make($name, $variant ?: 'solid', $request->only('class', 'style', 'width', 'height'))
            ->toResponse($request);
    }
}
