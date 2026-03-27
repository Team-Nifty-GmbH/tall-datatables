<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\View\ComponentAttributeBag;

class DataTableBladeDirectives
{
    public function getManifestVersion(string $file, ?string &$route = null): ?string
    {
        $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';

        if (! file_exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $version = last(explode('-', $manifest[$file]['file']));

        if ($route) {
            $route .= "?id={$version}";
        }

        return $version;
    }

    public function scripts(bool $absolute = false, array $attributes = []): string
    {
        $route = route(name: 'tall-datatables.assets.scripts', absolute: $absolute);
        $this->getManifestVersion('resources/js/tall-datatables.js', $route);

        $attributes = new ComponentAttributeBag($attributes);

        return <<<HTML
        <script src="{$route}" defer {$attributes->toHtml()}></script>
        HTML;
    }

    public function styles(bool $absolute = false, ?string $version = null): string
    {
        $route = route(name: 'tall-datatables.assets.styles', absolute: $absolute);

        if ($version === 'v4') {
            $route .= '?v=4';
        } else {
            $this->getManifestVersion('resources/css/v3.css', $route);
        }

        return "<link href=\"{$route}\" rel=\"stylesheet\" type=\"text/css\">";
    }
}
