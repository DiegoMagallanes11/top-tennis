<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourtsTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_can_view_courts_and_follow_the_home_link(): void
    {
        $court = Cancha::factory()->create(['estado_mantenimiento' => 'operativa']);

        $this->get(route('canchas.publico'))
            ->assertOk()
            ->assertSee($court->nombre)
            ->assertSee('href="'.route('welcome').'"', false)
            ->assertSee('Iniciar Sesión');

        $this->get(route('welcome'))->assertOk();
    }

    public function test_clients_can_view_courts_and_continue_to_booking(): void
    {
        $court = Cancha::factory()->create(['estado_mantenimiento' => 'operativa']);

        $this->actingAs(User::factory()->create())
            ->get(route('canchas.publico'))
            ->assertOk()
            ->assertSee($court->nombre)
            ->assertSee('Mi Panel')
            ->assertSee('href="'.route('reservas.disponibles').'"', false);
    }
}
