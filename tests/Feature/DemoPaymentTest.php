<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\BookingEvent;
use App\Models\BookingNotification;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ServicioPago;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        config(['pagos.demo_enabled' => true, 'pagos.yape_enabled' => false]);
    }

    private function start(string $method = 'Tarjeta'): Reserva
    {
        $slot = Horario::factory()->create(['hora_inicio' => '2026-10-14 14:00:00', 'hora_fin' => '2026-10-14 15:00:00']);
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), [
            'horario_id' => $slot->id, 'metodo_pago' => $method, 'clave_solicitud' => (string) Str::uuid(),
            'estado_pago' => 'aprobado', 'pago_demo' => false, 'monto_pagado' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('reservas.pagoDemo', Reserva::sole()));

        return Reserva::sole();
    }

    public function test_checkout_offers_yape_and_cards_without_cash_or_example_qr(): void
    {
        $slot = Horario::factory()->create(['hora_inicio' => '2026-10-14 14:00:00', 'hora_fin' => '2026-10-14 15:00:00']);
        $this->actingAs(User::factory()->create())->get(route('reservas.confirmar', $slot))->assertOk()
            ->assertSee('Modo de demostración')->assertSee('value="Yape"', false)->assertSee('value="Tarjeta"', false)
            ->assertDontSee('value="Efectivo"', false)->assertDontSee('images/yape.png')
            ->assertDontSee('name="numero_operacion"', false);
        $this->post(route('reservas.store'), ['horario_id' => $slot->id, 'metodo_pago' => 'Efectivo'])->assertSessionHasErrors('metodo_pago');
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_card_success_is_labelled_demo_and_cannot_authorize_entry(): void
    {
        $r = $this->start();
        $this->assertTrue($r->pago_demo);
        $this->assertSame('pendiente', $r->estado_pago);
        $this->assertSame($r->horario->tarifa->precio, $r->monto_pagado);
        $this->get(route('reservas.pagoDemo', $r))->assertOk()->assertSee('Simular pago aprobado')->assertDontSee('name="card_number"', false);
        $this->post(route('reservas.simularPago', $r), ['resultado' => 'aprobado'])->assertRedirect(route('reservas.ticket', $r));
        $r->refresh();
        $this->assertSame('confirmada', $r->estado_reserva);
        $this->assertSame('aprobado', $r->estado_pago);
        $this->assertFalse($r->estadoIngreso()['ok']);
        $this->get(route('reservas.ticket', $r))->assertOk()->assertSee('No válido para ingreso')->assertDontSee('✓ PAGADO');
        $this->get('/dashboard')->assertOk()->assertSee('Pago simulado aprobado')->assertDontSee('Presenta tu QR');
        $this->get(route('reservas.ticket.pdf', $r))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(0, BookingNotification::count());
        $this->post(route('reservas.simularPago', $r), ['resultado' => 'aprobado'])->assertSessionHasNoErrors();
        $this->assertSame(1, BookingEvent::where('accion', 'pago_demo_aprobado')->count());
    }

    public function test_yape_failure_can_be_retried_without_a_bank_reference(): void
    {
        $r = $this->start('Yape');
        $this->post(route('reservas.simularPago', $r), ['resultado' => 'rechazado'])->assertSessionHasErrors('general');
        $this->assertSame('pendiente', $r->fresh()->estado_pago);
        $this->assertNull($r->numero_operacion);
        $this->assertSame('reservado', $r->horario->estado);
        $this->post(route('reservas.simularPago', $r), ['resultado' => 'aprobado'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_other_customers_cannot_approve_and_expired_demo_cannot_be_paid(): void
    {
        $r = $this->start();
        $owner = auth()->user();
        $this->actingAs(User::factory()->create())->post(route('reservas.simularPago', $r), ['resultado' => 'aprobado'])->assertForbidden();
        $this->actingAs($owner);
        $this->travelTo($r->expira_at->copy()->addSecond());
        $this->post(route('reservas.simularPago', $r), ['resultado' => 'aprobado'])->assertSessionHasErrors('general');
        $this->get('/reservar')->assertOk();
        $this->assertSame('cancelada', $r->fresh()->estado_reserva);
        $this->assertSame('disponible', $r->horario->fresh()->estado);
    }

    public function test_production_disables_simulator_even_if_the_flag_is_enabled(): void
    {
        $r = $this->start();
        $this->app->instance('env', 'production');
        $this->assertFalse(ServicioPago::demoDisponible());
        $this->assertSame([], ServicioPago::metodosOnline());
        $this->get(route('reservas.pagoDemo', $r))->assertNotFound();
        $this->assertSame('pendiente', $r->fresh()->estado_pago);
    }

    public function test_staff_cash_remains_real_and_demo_totals_are_excluded(): void
    {
        $demo = $this->start();
        $this->post(route('reservas.simularPago', $demo), ['resultado' => 'aprobado'])->assertSessionHasNoErrors();
        $client = auth()->user();
        $admin = User::factory()->create();
        $admin->rol = Rol::Admin;
        $admin->save();
        $slot = Horario::factory()->create(['hora_inicio' => '2026-10-15 14:00:00', 'hora_fin' => '2026-10-15 15:00:00']);
        $this->actingAs($admin)->post(route('reservas.storeManual'), ['horario_id' => $slot->id, 'metodo_pago' => 'Efectivo', 'modo_cliente' => 'existente', 'user_id' => $client->id])->assertSessionHasNoErrors();
        $cash = Reserva::where('pago_demo', false)->sole();
        $this->assertSame('Efectivo', $cash->metodo_pago);
        $this->assertSame('aprobado', $cash->estado_pago);
        $this->get('/dashboard')->assertOk()->assertViewHas('stats', fn ($stats) => $stats['ingresos'] === (float) $cash->monto_pagado);
        $this->patch(route('reservas.confirmarPago', $demo))->assertSessionHasErrors('general');
    }
}
