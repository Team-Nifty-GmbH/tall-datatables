<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\User;
use Tests\TestCase;

class TraitsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define a test route for user detail
        Route::get('/users/{id}', function ($id) {
            return 'User details for: ' . $id;
        })->name('users.show');
    }

    #[Test]
    public function it_implements_broadcasts_events_trait(): void
    {
        $user = User::factory()->create();

        // Test broadcast channel method
        $channel = $user->broadcastChannel();
        $this->assertNotEmpty($channel);

        // Test static method
        $genericChannel = User::getBroadcastChannel();
        $this->assertNotEmpty($genericChannel);
    }

    #[Test]
    public function it_implements_datatable_user_settings_trait(): void
    {
        $user = User::factory()->create();

        // Test relationship method
        $this->assertTrue(method_exists($user, 'datatableUserSettings'));

        // Test get settings method
        $settings = $user->getDataTableSettings(User::class);
        $this->assertIsObject($settings);
    }

    #[Test]
    public function it_implements_frontend_attributes_trait(): void
    {
        $user = User::factory()->create();

        // Test type script attributes method
        $attributes = $user::typeScriptAttributes();
        $this->assertIsArray($attributes);

        // Test icon method
        $icon = $user::icon();
        $this->assertStringContainsString('user', $icon->toHtml());

        // Test detail route method
        $route = $user->detailRoute();
        $this->assertStringContainsString('users/' . $user->id, $route);
    }

    #[Test]
    public function it_implements_interfaces_with_data_tables_interface(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Test label method
        $this->assertEquals('Test User', $user->getLabel());

        // Test description method
        $this->assertEquals('test@example.com', $user->getDescription());

        // Test URL method
        $this->assertStringContainsString('users/' . $user->id, $user->getUrl());
    }
}
