<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Illuminate\Support\Facades\File;
use Livewire\Drawer\Utils;

class AssetController extends Controller
{
    /**
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function scripts(): mixed
    {
        $assetPath = dirname(__DIR__, 2) . '/dist/build/assets/';

        $path = request()->has('id')
            ? 'tall-datatables-' . request()->get('id') :
            null;

        $path = file_exists($assetPath . $path)
            ? $assetPath . $path
            : File::glob($assetPath . 'tall-datatables*.js')[0];

        return Utils::pretendResponseIsFile($path, 'text/javascript');
    }

    /**
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function styles(): mixed
    {
        $assetPath = dirname(__DIR__, 2) . '/dist/build/assets/';

        $path = request()->has('id')
            ? 'tall-datatables-' . request()->get('id') :
            null;
        $path = file_exists($assetPath . $path)
            ? $assetPath . $path
            : File::glob($assetPath . 'tall-datatables*.css')[0];

        return Utils::pretendResponseIsFile($path, 'text/css');
    }
}
