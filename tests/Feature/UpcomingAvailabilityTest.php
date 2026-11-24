<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpcomingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pagos.yape_enabled' => true, 'pagos.demo_enabled' => false]);
    }

    public function test_admin_is_warned_when_all_published_hours_are_past(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        ClubSetting::create(['id' => 1, 'reglas' => ['generacion_automatica' => false]]);
        $admin = User::factory()->create();
        $admin->rol = Rol::Admin;
        $admin->save();
        Horario::factory()->create(['hora_inicio' => '2026-10-12 12:00:00', 'hora_fin' => '2026-10-12 13:00:00']);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('No hay horarios futuros publicados')
            ->assertSee(route('horarios.create'));
    }

    public function test_reception_is_warned_to_ask_admin_before_dates_run_out(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        ClubSetting::create(['id' => 1, 'reglas' => ['generacion_automatica' => false]]);
        $reception = User::factory()->create();
        $reception->rol = Rol::Recepcionista;
        $reception->save();
        Horario::factory()->create(['hora_inicio' => '2026-10-14 12:00:00', 'hora_fin' => '2026-10-14 13:00:00']);

        $this->actingAs($reception)->get('/dashboard')->assertOk()
            ->assertSee('Publica los próximos horarios')
            ->assertSee('Solicita al administrador que publique los próximos horarios.')
            ->assertDontSee(route('horarios.create'));
    }

    public function test_warning_is_hidden_when_seven_calendar_days_are_covered(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        ClubSetting::create(['id' => 1, 'reglas' => ['generacion_automatica' => false]]);
        $admin = User::factory()->create();
        $admin->rol = Rol::Admin;
        $admin->save();
        Horario::factory()->create(['hora_inicio' => '2026-10-19 12:00:00', 'hora_fin' => '2026-10-19 13:00:00']);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertDontSee('Publica los próximos horarios')
            ->assertDontSee('No hay horarios futuros publicados');
    }

    public function test_client_can_book_a_newly_published_future_slot_and_open_ticket(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        $client = User::factory()->create();
        $slot = Horario::factory()->create(['hora_inicio' => '2026-10-13 12:00:00', 'hora_fin' => '2026-10-13 13:00:00']);

        $this->actingAs($client)->get('/reservar')->assertOk()->assertSee($slot->cancha->nombre);
        $this->get(route('reservas.confirmar', $slot))->assertOk();
        $response = $this->post(route('reservas.store'), [
            'horario_id' => $slot->id,
            'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid(),
            'clave_solicitud' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();
        $booking = Reserva::sole();
        $response->assertRedirect(route('reservas.ticket', $booking));
        $this->assertSame($client->id, $booking->user_id);
        $this->assertSame('pendiente', $booking->estado_reserva);
        $this->assertSame('reservado', $slot->fresh()->estado);
        $this->get(route('reservas.ticket', $booking))->assertOk()->assertSee($booking->codigo_validacion);
        $this->get('/dashboard')->assertOk()->assertDontSee('Publica los próximos horarios');
    }
}
