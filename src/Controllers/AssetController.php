<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Livewire\Controllers\CanPretendToBeAFile;

class AssetController extends Controller
{
    use CanPretendToBeAFile;

    /**
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function scripts(): mixed
    {
        $path = dirname(__DIR__, 2) . '/dist/build/assets/';
        $path .= request()->has('id') ? 'tall-datatables-' . request()->get('id') : 'tall-datatables.js';

        return $this->pretendResponseIsFile(
            $path,
            'application/javascript'
        );
    }

    /**
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function styles(): mixed
    {
        $path = dirname(__DIR__, 2) . '/dist/build/assets/';
        $path .= request()->has('id') ? 'tall-datatables-' . request()->get('id') : 'tall-datatables.css';

        return $this->pretendResponseIsFile(
            $path,
            'text/css'
        );
    }
}
