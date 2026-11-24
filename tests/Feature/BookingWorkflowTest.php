<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\BookingEvent;
use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\GestorReservas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pagos.yape_enabled' => true, 'pagos.demo_enabled' => false]);
    }

    public function test_pending_migration_shows_a_clear_notice_instead_of_fake_success(): void
    {
        $user = User::factory()->create();
        Schema::shouldReceive('hasColumn')->with('reservas', 'estado_reserva')->once()->andReturnFalse();
        $this->actingAs($user)->get('/dashboard')->assertStatus(503)->assertSee('Actualización en curso')->assertSee('Ninguna reserva se confirmó');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->rol = Rol::Admin;
        $user->save();

        return $user;
    }

    public function test_registration_does_not_accept_privilege_escalation(): void
    {
        $this->post('/register', ['name' => 'Cliente', 'email' => 'persona@example.com', 'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!', 'rol' => 'admin', 'activo' => false])->assertRedirect('/dashboard');
        $user = User::where('email', 'persona@example.com')->firstOrFail();
        $this->assertSame(Rol::Cliente, $user->rol);
        $this->assertTrue($user->activo);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/reservas')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'ValidPassword123!'])->assertRedirect('/reservas');
        $this->assertAuthenticatedAs($user);
    }

    public function test_yape_is_pending_until_staff_verifies_the_payment(): void
    {
        config(['pagos.yape_enabled' => true]);
        $client = User::factory()->create();
        $h = Horario::factory()->create();
        $this->actingAs($client)->post(route('reservas.store'), ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => '00011122'])->assertSessionHasNoErrors();
        $r = Reserva::firstOrFail();
        $this->assertSame('pendiente', $r->estado_pago);
        $this->assertSame('pendiente', $r->estado_reserva);
        $this->assertFalse($r->estadoIngreso()['ok']);
        $this->get(route('reservas.ticket', $r))->assertOk()->assertSee($r->codigo_validacion);
        $this->get(route('reservas.index'))->assertOk()->assertSee($r->codigo_validacion);
        $this->actingAs($this->admin())->patch(route('reservas.confirmarPago', $r))->assertSessionHasNoErrors();
        $this->assertSame('confirmada', $r->fresh()->estado_reserva);
        $this->assertTrue($r->fresh()->estadoIngreso()['ok']);
        $this->assertDatabaseHas('booking_events', ['reserva_id' => $r->id, 'accion' => 'confirmar']);
    }

    public function test_retrying_the_same_submission_returns_the_saved_booking_once(): void
    {
        config(['pagos.yape_enabled' => true]);
        $h = Horario::factory()->create();
        $user = User::factory()->create();
        $key = (string) Str::uuid();
        $data = ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => 'retry123', 'clave_solicitud' => $key];
        $this->actingAs($user)->post(route('reservas.store'), $data)->assertSessionHasNoErrors();
        $r = Reserva::firstOrFail();
        $this->post(route('reservas.store'), $data)->assertRedirect(route('reservas.ticket', $r))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservas', 1);
        $this->assertDatabaseCount('booking_notifications', 1);
    }

    public function test_cancellation_preserves_history_and_releases_the_slot(): void
    {
        $r = Reserva::factory()->yape()->create();
        $this->actingAs($r->user)->delete(route('reservas.cancelar', $r))->assertSessionHasNoErrors();
        $this->assertSame('cancelada', $r->fresh()->estado_reserva);
        $this->assertSame('aprobado', $r->fresh()->estado_pago);
        $this->assertSame('disponible', $r->horario->fresh()->estado);
        $this->get(route('reservas.index'))->assertSee($r->codigo_validacion)->assertSee('Cancelada');
        $this->assertFalse($r->fresh()->estadoIngreso()['ok']);
    }

    public function test_client_can_move_a_paid_booking_to_a_slot_of_the_same_price(): void
    {
        $r = Reserva::factory()->yape()->create();
        $new = Horario::factory()->create(['tarifa_id' => $r->horario->tarifa_id]);
        $old = $r->horario;
        $this->actingAs($r->user)->patch(route('reservas.update', $r), ['horario_id' => $new->id])->assertSessionHasNoErrors();
        $this->assertSame($new->id, $r->fresh()->horario_id);
        $this->assertSame('disponible', $old->fresh()->estado);
        $this->assertSame('reservado', $new->fresh()->estado);
        $this->assertDatabaseHas('booking_notifications', ['reserva_id' => $r->id, 'evento' => 'modificar']);
    }

    public function test_cancel_or_move_after_the_policy_deadline_is_rejected(): void
    {
        $r = Reserva::factory()->yape()->create();
        $r->horario->update(['hora_inicio' => now()->addHour(), 'hora_fin' => now()->addHours(2)]);
        $new = Horario::factory()->create();
        $this->actingAs($r->user)->delete(route('reservas.cancelar', $r))->assertSessionHasErrors('general');
        $this->patch(route('reservas.update', $r), ['horario_id' => $new->id])->assertSessionHasErrors('general');
        $this->assertSame('reservado', $r->horario->fresh()->estado);
    }

    public function test_admin_can_reject_and_client_sees_the_change(): void
    {
        $r = Reserva::factory()->efectivoPendiente()->create();
        $this->actingAs($this->admin())->patch(route('reservas.gestionar', $r), ['accion' => 'rechazar', 'motivo' => 'Comprobante no válido'])->assertSessionHasNoErrors();
        $this->assertSame('rechazada', $r->fresh()->estado_reserva);
        $this->assertSame('disponible', $r->horario->fresh()->estado);
        $this->actingAs($r->user)->get(route('reservas.ticket', $r))->assertSee('Comprobante no válido')->assertSee('Rechazada');
        $this->assertFalse($r->fresh()->estadoIngreso()['ok']);
    }

    public function test_normal_user_cannot_manage_rules_or_another_booking(): void
    {
        $r = Reserva::factory()->yape()->create();
        $this->actingAs(User::factory()->create());
        $this->patch(route('reservas.update', $r), ['horario_id' => $r->horario_id])->assertForbidden();
        $this->patch(route('reservas.gestionar', $r), ['accion' => 'rechazar', 'motivo' => 'No'])->assertForbidden();
        $this->get(route('club.edit'))->assertForbidden();
        $this->get(route('club.auditoria'))->assertForbidden();
    }

    public function test_admin_panel_and_filters_work_without_exposing_other_clients(): void
    {
        $r = Reserva::factory()->yape()->create();
        $other = Reserva::factory()->yape()->create();
        $this->actingAs($this->admin())->get('/dashboard')->assertOk();
        $this->get(route('reservas.index', ['buscar' => $r->codigo_validacion, 'estado' => 'confirmada']))->assertOk()->assertSee($r->codigo_validacion)->assertDontSee($other->codigo_validacion);
        $this->actingAs($r->user)->get(route('reservas.index', ['cliente' => $other->user_id]))->assertSee($r->codigo_validacion)->assertDontSee($other->codigo_validacion);
    }

    public function test_holidays_rest_and_opening_rules_apply_on_the_server(): void
    {
        $h = Horario::factory()->create();
        $user = User::factory()->create();
        ClubSetting::create(['id' => 1, 'reglas' => ['festivos' => [$h->hora_inicio->toDateString()]]]);
        $this->actingAs($user)->get(route('reservas.disponibles'))->assertOk()->assertViewHas('horarios', fn ($hs) => ! $hs->contains('id', $h->id));
        $this->post(route('reservas.store'), ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid()])->assertSessionHasErrors('horario_id');
        $this->assertDatabaseCount('reservas', 0);
        $this->assertSame('disponible', $h->fresh()->estado);
    }

    public function test_a_separate_overlapping_slot_cannot_be_booked(): void
    {
        $r = Reserva::factory()->yape()->create();
        $overlap = Horario::factory()->create(['cancha_id' => $r->horario->cancha_id, 'hora_inicio' => $r->horario->hora_inicio->copy()->addMinutes(15), 'hora_fin' => $r->horario->hora_fin->copy()->addMinutes(15)]);
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), ['horario_id' => $overlap->id, 'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid()])->assertSessionHasErrors('horario_id');
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_failure_to_write_the_audit_rolls_back_the_entire_booking(): void
    {
        $h = Horario::factory()->create();
        $u = User::factory()->create();
        $this->actingAs($u);
        BookingEvent::creating(function () {
            throw new \RuntimeException('Fallo provocado por la prueba');
        });
        try {
            app(GestorReservas::class)->crear($u, ['horario_id' => $h->id, 'metodo_pago' => 'Efectivo']);
            $this->fail('Debe fallar');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo provocado por la prueba', $e->getMessage());
        } finally {
            BookingEvent::flushEventListeners();
        }
        $this->assertDatabaseCount('reservas', 0);
        $this->assertSame('disponible', $h->fresh()->estado);
    }

    public function test_unconfigured_mail_is_never_reported_as_sent(): void
    {
        Mail::fake();
        $h = Horario::factory()->create();
        $u = User::factory()->create();
        config(['mail.notifications_enabled' => false]);
        $this->actingAs($u)->post(route('reservas.store'), ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->artisan('reservas:enviar-avisos')->assertExitCode(0);
        $this->assertDatabaseHas('booking_notifications', ['estado' => 'sin_configurar']);
        Mail::assertNothingSent();
    }

    public function test_maintenance_cancels_pending_bookings_without_claiming_refunds(): void
    {
        $r = Reserva::factory()->efectivoPendiente()->create();
        $this->actingAs($this->admin())->post(route('canchas.mantenimiento', $r->horario->cancha), ['motivo_mantenimiento' => 'Reparación', 'fin_mantenimiento' => $r->horario->hora_fin->copy()->addHour()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $this->assertSame('cancelada', $r->fresh()->estado_reserva);
        $this->assertSame('disponible', $r->horario->fresh()->estado);
        $this->assertDatabaseHas('booking_notifications', ['reserva_id' => $r->id, 'evento' => 'cancelada_por_mantenimiento']);
    }

    public function test_password_reset_rejects_invalid_tokens(): void
    {
        $u = User::factory()->create();
        $this->post('/reset-password', ['token' => 'token-falso', 'email' => $u->email, 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $u->fresh()->password));
    }

    public function test_notification_failure_is_recorded_and_retried_without_losing_booking(): void
    {
        config(['mail.notifications_enabled' => true, 'mail.default' => 'smtp', 'mail.reminder_hours' => 0]);
        $h = Horario::factory()->create();
        $u = User::factory()->create();
        $this->actingAs($u)->post(route('reservas.store'), ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $attempt = 0;
        Mail::shouldReceive('send')->twice()->andReturnUsing(function () use (&$attempt) {
            if (++$attempt === 1) {
                throw new \RuntimeException('Servicio temporalmente caído');
            }
        });
        $this->artisan('reservas:enviar-avisos')->assertExitCode(0);
        $this->assertDatabaseHas('booking_notifications', ['estado' => 'error', 'intentos' => 1]);
        $this->artisan('reservas:enviar-avisos')->assertExitCode(0);
        $this->assertDatabaseHas('booking_notifications', ['estado' => 'enviado', 'intentos' => 2]);
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_admin_settings_are_persistent_and_slots_use_configured_duration(): void
    {
        $admin = $this->admin();
        $cancha = Cancha::factory()->operativa()->create();
        $tarifa = Tarifa::factory()->create();
        $rules = ClubSetting::reglas();
        $rules['duracion_minutos'] = 90;
        $rules['festivos'] = '';
        $this->actingAs($admin)->patch(route('club.update'), $rules)->assertSessionHasNoErrors();
        $this->get(route('club.edit'))->assertOk()->assertSee('90');
        $fecha = now()->addDays(3)->toDateString();
        $this->post(route('horarios.store'), ['fecha_inicio' => $fecha, 'fecha_fin' => $fecha, 'hora_desde' => 9, 'hora_hasta' => 12, 'canchas' => [$cancha->id], 'tarifa_dia' => $tarifa->id])->assertSessionHasNoErrors();
        $slots = Horario::where('cancha_id', $cancha->id)->get();
        $this->assertCount(2, $slots);
        foreach ($slots as $h) {
            $this->assertEquals(90, $h->hora_inicio->diffInMinutes($h->hora_fin));
        }
        $this->get(route('club.auditoria'))->assertOk()->assertSee('reglas actualizadas');
    }

    public function test_paid_finished_reservation_can_be_completed_but_future_one_cannot(): void
    {
        $r = Reserva::factory()->yape()->create();
        $this->actingAs($this->admin());
        $this->patch(route('reservas.gestionar', $r), ['accion' => 'completar'])->assertSessionHasErrors('general');
        $r->horario->update(['hora_inicio' => now()->subHours(2), 'hora_fin' => now()->subHour()]);
        $this->patch(route('reservas.gestionar', $r), ['accion' => 'completar'])->assertSessionHasNoErrors();
        $this->assertSame('completada', $r->fresh()->estado_reserva);
    }

    public function test_yape_cannot_be_selected_when_payment_details_are_unconfigured(): void
    {
        config(['pagos.yape_enabled' => false]);
        $h = Horario::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), ['horario_id' => $h->id, 'metodo_pago' => 'Yape', 'numero_operacion' => 'falso123'])->assertSessionHasErrors('metodo_pago');
        $this->assertDatabaseCount('reservas', 0);
    }
}
