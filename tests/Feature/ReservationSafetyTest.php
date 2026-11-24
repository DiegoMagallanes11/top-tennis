<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pagos.yape_enabled' => true, 'pagos.demo_enabled' => false]);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->rol = Rol::Recepcionista;
        $user->save();

        return $user;
    }

    public function test_booking_routes_require_authentication_and_keep_the_rate_limit(): void
    {
        $this->post(route('reservas.store'))->assertRedirect(route('login'));
        $this->post(route('reservas.storeManual'))->assertRedirect(route('login'));

        foreach (['reservas.store', 'reservas.storeManual'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('throttle:10,1', $route->gatherMiddleware());
        }
    }

    public function test_client_cannot_create_a_manual_booking(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservas.storeManual'))
            ->assertForbidden();
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_disabled_account_cannot_keep_using_an_existing_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->activo = false;
        $user->save();

        $this->get(route('reservas.disponibles'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_client_booking_preserves_price_and_prevents_a_second_booking(): void
    {
        Mail::fake();
        $horario = Horario::factory()->create();
        $price = $horario->tarifa->precio;
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), [
            'horario_id' => $horario->id,
            'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();

        $reserva = Reserva::firstOrFail();
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->estado_pago);
        $this->assertEquals($price, $reserva->monto_pagado);
        $this->assertSame('reservado', $horario->fresh()->estado);

        $this->actingAs(User::factory()->create())->post(route('reservas.store'), [
            'horario_id' => $horario->id,
            'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid(),
        ])->assertSessionHasErrors('horario_id');
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_staff_cannot_manually_book_a_past_or_maintenance_slot(): void
    {
        $this->actingAs($this->staff());
        $past = Horario::factory()->create([
            'hora_inicio' => now()->subHours(2), 'hora_fin' => now()->subHour(),
        ]);
        $maintenance = Horario::factory()->create();
        $maintenance->cancha->update(['estado_mantenimiento' => 'en_mantenimiento']);

        foreach ([$past, $maintenance] as $horario) {
            $this->post(route('reservas.storeManual'), [
                'horario_id' => $horario->id, 'metodo_pago' => 'Efectivo',
                'modo_cliente' => 'nuevo', 'nuevo_nombre' => 'Cliente en caja',
            ])->assertSessionHasErrors('horario_id');
            $this->assertSame('disponible', $horario->fresh()->estado);
        }

        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_manual_booking_accepts_only_active_clients(): void
    {
        $staff = $this->staff();
        $inactive = User::factory()->create();
        $inactive->activo = false;
        $inactive->save();
        $deleted = User::factory()->create();
        $deleted->delete();
        $horario = Horario::factory()->create();

        foreach ([$staff, $inactive, $deleted] as $invalidClient) {
            $this->actingAs($staff)->post(route('reservas.storeManual'), [
                'horario_id' => $horario->id, 'metodo_pago' => 'Efectivo',
                'modo_cliente' => 'existente', 'user_id' => $invalidClient->id,
            ])->assertSessionHasErrors('user_id');
        }
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_staff_can_create_a_valid_manual_booking(): void
    {
        $horario = Horario::factory()->create();
        $client = User::factory()->create();
        $this->actingAs($this->staff())->post(route('reservas.storeManual'), [
            'horario_id' => $horario->id, 'metodo_pago' => 'Efectivo',
            'modo_cliente' => 'existente', 'user_id' => $client->id,
        ])->assertRedirect(route('reservas.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservas', [
            'user_id' => $client->id, 'horario_id' => $horario->id,
            'estado_pago' => Reserva::ESTADO_APROBADO,
        ]);
    }

    public function test_manual_booking_cannot_reuse_a_yape_operation(): void
    {
        $existing = Reserva::factory()->yape()->create(['numero_operacion' => '12345678']);
        $horario = Horario::factory()->create();
        $this->actingAs($this->staff())->post(route('reservas.storeManual'), [
            'horario_id' => $horario->id, 'metodo_pago' => 'Yape',
            'numero_operacion' => '12345678', 'modo_cliente' => 'existente',
            'user_id' => $existing->user_id,
        ])->assertSessionHasErrors('numero_operacion');
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_another_client_cannot_download_or_cancel_a_ticket(): void
    {
        $reserva = Reserva::factory()->yape()->create();
        $this->actingAs(User::factory()->create());
        $this->get(route('reservas.ticket.pdf', $reserva))->assertForbidden();
        $this->delete(route('reservas.cancelar', $reserva))->assertForbidden();
        $this->assertSame('reservado', $reserva->horario->fresh()->estado);
    }

    public function test_cancelled_maintenance_ticket_cannot_release_a_new_booking(): void
    {
        $old = Reserva::factory()->create(['estado_pago' => Reserva::ESTADO_CANCELADO_MANT]);
        $new = Reserva::factory()->yape()->create(['horario_id' => $old->horario_id]);

        $this->actingAs($this->staff())->delete(route('reservas.cancelar', $old))
            ->assertSessionHasErrors('general');
        $this->assertSame('reservado', $old->horario->fresh()->estado);
        $this->assertNotNull($new->fresh());
        $this->assertFalse($old->estadoIngreso()['ok']);
    }

    public function test_payment_deadline_is_enforced_at_the_exact_expiration_time(): void
    {
        $this->freezeTime();
        $reserva = Reserva::factory()->efectivoPendiente()->create(['expira_at' => now()]);

        $this->assertTrue($reserva->estaVencida());
        $this->assertFalse($reserva->estadoIngreso()['ok']);
        $this->actingAs($this->staff())->patch(route('reservas.confirmarPago', $reserva))
            ->assertSessionHasErrors('general');
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->fresh()->estado_pago);
    }

    public function test_availability_releases_unpaid_expired_bookings_without_scheduler(): void
    {
        $expired = Reserva::factory()->efectivoPendiente()->create(['expira_at' => now()->subMinute()]);
        $paid = Reserva::factory()->yape()->create();
        $this->actingAs(User::factory()->create())->get(route('reservas.disponibles'))->assertOk();
        $this->assertSame(Reserva::ESTADO_ANULADA, $expired->fresh()->estado_pago);
        $this->assertSame('disponible', $expired->horario->fresh()->estado);
        $this->assertSame('reservado', $paid->horario->fresh()->estado);
    }

    public function test_receptionist_can_register_entry_only_once(): void
    {
        $reserva = Reserva::factory()->yape()->create();
        $token = $reserva->asegurarToken();
        $this->actingAs($this->staff())->get(route('validacion.mostrar', $token))
            ->assertOk()->assertSee('Registrar ingreso');
        $this->post(route('validacion.usar', $token))->assertSessionHasNoErrors();
        $entered = $reserva->fresh()->usado_at;
        $this->assertNotNull($entered);
        $this->post(route('validacion.usar', $token))->assertSessionHasErrors('general');
        $this->assertEquals($entered, $reserva->fresh()->usado_at);
    }
}
