<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña');
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        $this->get('/')->assertRedirectToRoute('login');
    }

    public function test_login_is_rate_limited(): void
    {
        $route = Route::getRoutes()->getByName('login.store');

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->middleware());
    }

    public function test_named_routes_are_unique(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->values();

        $this->assertCount($names->unique()->count(), $names);
    }
}
