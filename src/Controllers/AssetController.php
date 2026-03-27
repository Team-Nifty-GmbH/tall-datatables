<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Illuminate\Support\Facades\File;
use Livewire\Drawer\Utils;

class AssetController extends Controller
{
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

    public function styles(): mixed
    {
        if (request()->get('v') === '4') {
            $path = dirname(__DIR__, 2) . '/dist/tall-datatables.css';

            if (file_exists($path)) {
                return Utils::pretendResponseIsFile($path, 'text/css');
            }
        }

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
