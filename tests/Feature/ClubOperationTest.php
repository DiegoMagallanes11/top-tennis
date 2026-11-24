<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\BookingEvent;
use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\GestorReservas;
use App\Services\ReglasReserva;
use Carbon\Carbon;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubOperationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(Rol $rol = Rol::Admin): User
    {
        $user = User::factory()->create();
        $user->rol = $rol;
        $user->save();

        return $user;
    }

    private function slot(string $inicio): Horario
    {
        return Horario::factory()->create(['hora_inicio' => $inicio, 'hora_fin' => Carbon::parse($inicio)->addHour()]);
    }

    public function test_weekend_closing_times_are_enforced_and_home_uses_the_same_rules(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));
        $rules = app(ReglasReserva::class);
        $this->assertTrue($rules->permite($this->slot('2026-10-10 19:00:00')));
        $this->assertFalse($rules->permite($this->slot('2026-10-10 20:00:00')));
        $this->assertTrue($rules->permite($this->slot('2026-10-11 14:00:00')));
        $this->assertFalse($rules->permite($this->slot('2026-10-11 15:00:00')));
        $this->get('/')->assertOk()->assertSee('Sáb 06:00–20:00')->assertSee('Dom 06:00–15:00');
    }

    public function test_legacy_rules_are_preserved_until_explicitly_updated(): void
    {
        ClubSetting::create(['id' => 1, 'reglas' => ['apertura' => '08:00', 'cierre' => '21:00']]);
        $this->assertSame(['apertura' => '08:00', 'cierre' => '21:00'], ClubSetting::horarioDelDia(0));
    }

    public function test_new_demo_uses_arcilla_and_respects_weekend_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));
        $this->seed(DemoSeeder::class);
        $this->assertSame(['Arcilla'], Cancha::pluck('tipo_superficie')->unique()->values()->all());
        $this->assertSame('2026-10-10 20:00:00', Horario::whereDate('hora_inicio', '2026-10-10')->max('hora_fin'));
        $this->assertSame('2026-10-11 15:00:00', Horario::whereDate('hora_inicio', '2026-10-11')->max('hora_fin'));
        $this->assertSame('2026-10-09 22:00:00', Horario::whereDate('hora_inicio', '2026-10-09')->max('hora_fin'));
    }

    public function test_admin_can_save_independent_hours_and_invalid_hours_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $rules = ClubSetting::reglas();
        $rules['festivos'] = '';
        $rules['horarios_semana'][0] = ['apertura' => '09:00', 'cierre' => '14:00'];
        $this->patch(route('club.update'), $rules)->assertSessionHasNoErrors();
        $this->assertSame('14:00', ClubSetting::horarioDelDia(0)['cierre']);
        $rules['horarios_semana'][0]['cierre'] = '08:00';
        $this->patch(route('club.update'), $rules)->assertSessionHasErrors('horarios_semana.0.cierre');
        $this->assertSame('14:00', ClubSetting::horarioDelDia(0)['cierre']);
    }

    public function test_client_dashboard_shows_only_their_earliest_active_match_and_deadline(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));
        $owner = User::factory()->create();
        $other = User::factory()->create(['name' => 'Jugador ajeno']);
        app(GestorReservas::class)->crear($other, ['horario_id' => $this->slot('2026-10-12 11:00:00')->id, 'metodo_pago' => 'Efectivo']);
        $next = app(GestorReservas::class)->crear($owner, ['horario_id' => $this->slot('2026-10-12 12:00:00')->id, 'metodo_pago' => 'Efectivo']);
        $later = app(GestorReservas::class)->crear($owner, ['horario_id' => $this->slot('2026-10-13 12:00:00')->id, 'metodo_pago' => 'Efectivo']);
        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertSee('Mi próximo partido')->assertSee('12/10/2026 11:30')
            ->assertSee(route('reservas.ticket', $next), false)
            ->assertDontSee(route('reservas.ticket', $later), false)->assertDontSee('Jugador ajeno');
    }

    public function test_client_without_a_match_gets_a_clear_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Todavía no tienes un próximo partido');
    }

    public function test_reception_dashboard_has_today_agenda_and_pending_payments_sorted_by_deadline(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));
        $client = User::factory()->create();
        $first = app(GestorReservas::class)->crear($client, ['horario_id' => $this->slot('2026-10-12 11:00:00')->id, 'metodo_pago' => 'Efectivo']);
        $second = app(GestorReservas::class)->crear($client, ['horario_id' => $this->slot('2026-10-13 12:00:00')->id, 'metodo_pago' => 'Efectivo']);
        $response = $this->actingAs($this->staff(Rol::Recepcionista))->get('/dashboard')->assertOk()
            ->assertSee('Agenda de hoy')->assertSee('Pagos pendientes (2)')->assertDontSee('Tasa no-show');
        $this->assertSame([$first->id], $response->viewData('agendaHoy')->pluck('id')->all());
        $this->assertSame([$first->id, $second->id], $response->viewData('pagosPendientes')->pluck('id')->all());
    }

    public function test_disabled_yape_is_hidden_and_payment_and_cancellation_deadlines_are_shown(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));
        config(['pagos.yape_enabled' => false]);
        $this->actingAs(User::factory()->create())->get(route('reservas.confirmar', $this->slot('2026-10-12 14:00:00')))
            ->assertOk()->assertSee('Revisa y solicita tu reserva')->assertSee('12/10/2026 13:30')
            ->assertSee('12/10/2026 12:00')->assertDontSee('value="Yape"', false);
    }

    public function test_alignment_requires_apply_flag_and_preserves_existing_bookings(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));
        $court = Cancha::factory()->create(['tipo_superficie' => 'Dura', 'imagen' => 'Dura.jpeg']);
        $slot = Horario::factory()->create(['cancha_id' => $court->id, 'hora_inicio' => '2026-10-11 18:00:00', 'hora_fin' => '2026-10-11 19:00:00', 'estado' => 'reservado']);
        $reservation = Reserva::create(['user_id' => User::factory()->create()->id, 'horario_id' => $slot->id, 'metodo_pago' => 'Efectivo', 'estado_pago' => 'aprobado', 'monto_pagado' => 50, 'codigo_validacion' => Reserva::generarCodigoValidacion()]);
        $before = $reservation->fresh()->getAttributes();
        $this->artisan('club:alinear-datos')->assertSuccessful();
        $this->assertSame('Dura', $court->fresh()->tipo_superficie);
        $this->artisan('club:alinear-datos', ['--aplicar' => true])->assertSuccessful();
        $this->assertSame('Arcilla', $court->fresh()->tipo_superficie);
        $this->assertSame('Arcilla.jpeg', $court->fresh()->imagen);
        $this->assertSame($before, $reservation->fresh()->getAttributes());
        $this->assertSame('reservado', $slot->fresh()->estado);
        $event = BookingEvent::where('accion', 'datos_club_alineados')->firstOrFail();
        $this->assertSame([$reservation->id], $event->datos['reservas_a_revisar']);
        $this->assertEquals(now(), $event->created_at);
        $this->artisan('club:alinear-datos', ['--aplicar' => true])->assertSuccessful();
        $this->assertSame(1, BookingEvent::where('accion', 'datos_club_alineados')->count());
    }
}
