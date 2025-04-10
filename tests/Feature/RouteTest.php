<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteTest extends TestCase
{
    #[Test]
    public function it_can_access_assets_scripts_route(): void
    {
        $response = $this->get(route('tall-datatables.assets.scripts'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/javascript; charset=UTF-8');
    }

    #[Test]
    public function it_can_access_assets_styles_route(): void
    {
        $response = $this->get(route('tall-datatables.assets.styles'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }
}
