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
use App\Services\PublicadorHorarios;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticSchedulesTest extends TestCase
{
    use RefreshDatabase;

    private function prepareClub(array $extra = []): Cancha
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        $day = Tarifa::factory()->create(['nombre_tarifa' => 'Tarifa Día', 'precio' => 50]);
        $night = Tarifa::factory()->create(['nombre_tarifa' => 'Tarifa Noche', 'precio' => 60]);
        ClubSetting::create(['id' => 1, 'reglas' => array_replace(ClubSetting::reglas(), [
            'tarifa_dia_id' => $day->id, 'tarifa_noche_id' => $night->id, 'dias_publicacion' => 7,
        ], $extra)]);

        return Cancha::factory()->create(['estado_mantenimiento' => 'operativa']);
    }

    public function test_dates_roll_forward_after_a_week_without_manual_publication(): void
    {
        $this->prepareClub();
        $publisher = app(PublicadorHorarios::class);
        $this->assertGreaterThan(0, $publisher->asegurar()['creados']);
        $initial = Horario::count();
        $this->assertSame(0, $publisher->asegurar()['creados']);
        $this->assertSame($initial, Horario::count());
        $this->assertSame(1, BookingEvent::where('accion', 'horarios_publicados_automaticamente')->count());
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00'));
        $this->assertGreaterThan(0, $publisher->asegurar()['creados']);
        $this->assertSame('2026-10-20', Carbon::parse(Horario::max('hora_inicio'))->toDateString());
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->assertGreaterThan(0, $publisher->asegurar()['creados']);
        $this->assertSame('2026-10-26', Carbon::parse(Horario::max('hora_inicio'))->toDateString());
        $this->assertSame($initial, Horario::whereDate('hora_inicio', '<', '2026-10-20')->count());
    }

    public function test_generation_respects_weekends_holidays_breaks_and_maintenance(): void
    {
        $court = $this->prepareClub(['festivos' => ['2026-10-14'], 'descanso_inicio' => '12:00', 'descanso_fin' => '14:00']);
        $maintenance = Cancha::factory()->create(['estado_mantenimiento' => 'en_mantenimiento']);
        app(PublicadorHorarios::class)->asegurar();
        $this->assertSame(0, Horario::where('cancha_id', $maintenance->id)->count());
        $this->assertSame(0, Horario::whereDate('hora_inicio', '2026-10-14')->count());
        $this->assertSame(0, Horario::where('cancha_id', $court->id)->where('hora_inicio', '<', '2026-10-15 14:00:00')->where('hora_fin', '>', '2026-10-15 12:00:00')->count());
        $this->assertSame('2026-10-17 20:00:00', Horario::whereDate('hora_inicio', '2026-10-17')->max('hora_fin'));
        $this->assertSame('2026-10-18 15:00:00', Horario::whereDate('hora_inicio', '2026-10-18')->max('hora_fin'));
    }

    public function test_reserved_custom_and_removed_slots_are_never_overwritten(): void
    {
        $court = $this->prepareClub();
        $custom = Horario::factory()->create(['cancha_id' => $court->id, 'hora_inicio' => '2026-10-14 10:30:00', 'hora_fin' => '2026-10-14 12:00:00', 'estado' => 'reservado']);
        $booking = Reserva::factory()->create(['horario_id' => $custom->id]);
        $removed = Horario::factory()->create(['cancha_id' => $court->id, 'hora_inicio' => '2026-10-14 14:00:00', 'hora_fin' => '2026-10-14 15:00:00']);
        $removed->delete();
        $slotBefore = $custom->fresh()->getAttributes();
        $bookingBefore = $booking->fresh()->getAttributes();
        app(PublicadorHorarios::class)->asegurar();
        $this->assertSame($slotBefore, $custom->fresh()->getAttributes());
        $this->assertSame($bookingBefore, $booking->fresh()->getAttributes());
        $this->assertSame(1, Horario::where('cancha_id', $court->id)->where('hora_inicio', '<', '2026-10-14 12:00:00')->where('hora_fin', '>', '2026-10-14 10:30:00')->count());
        $this->assertSame(0, Horario::where('cancha_id', $court->id)->where('hora_inicio', '<', '2026-10-14 15:00:00')->where('hora_fin', '>', '2026-10-14 14:00:00')->count());
    }

    public function test_tariffs_duration_and_booking_horizon_follow_configuration(): void
    {
        $this->prepareClub(['duracion_minutos' => 30, 'anticipacion_dias' => 2]);
        app(PublicadorHorarios::class)->asegurar();
        $day = Horario::where('hora_inicio', '2026-10-14 17:30:00')->firstOrFail();
        $night = Horario::where('hora_inicio', '2026-10-14 18:00:00')->firstOrFail();
        $this->assertSame(30, (int) $day->hora_inicio->diffInMinutes($day->hora_fin));
        $this->assertSame('50.00', $day->tarifa->precio);
        $this->assertSame('60.00', $night->tarifa->precio);
        $this->assertSame('2026-10-14', Carbon::parse(Horario::max('hora_inicio'))->toDateString());
    }

    public function test_opening_customer_availability_generates_slots_and_daily_counter(): void
    {
        $court = $this->prepareClub();
        $client = User::factory()->create();
        $this->actingAs($client)->get('/reservar')->assertOk()->assertSee($court->nombre)->assertSee("fechaFiltro: '2026-10-13'", false);
        $todayCount = Horario::whereDate('hora_inicio', today())->where('hora_inicio', '>', now()->addMinutes(30))->count();
        $this->get('/dashboard')->assertOk()->assertSee('Cupos disponibles hoy')->assertDontSee('Próximos 7 días')
            ->assertViewHas('stats', fn ($stats) => $stats['disponibles'] === $todayCount);
        $this->travelTo(Carbon::parse('2026-10-13 22:30:00'));
        $this->get('/reservar')->assertOk()->assertSee("fechaFiltro: '2026-10-14'", false);
    }

    public function test_missing_or_ambiguous_tariffs_do_not_create_arbitrary_prices(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
        Cancha::factory()->create();
        Tarifa::factory()->create(['nombre_tarifa' => 'Tarifa Día']);
        Tarifa::factory()->create(['nombre_tarifa' => 'Tarifa Día']);
        Tarifa::factory()->create(['nombre_tarifa' => 'Tarifa Noche']);
        $this->assertSame('faltan_tarifas', app(PublicadorHorarios::class)->asegurar()['estado']);
        $this->assertSame(0, Horario::count());
        $admin = User::factory()->create();
        $admin->rol = Rol::Admin;
        $admin->save();
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Configura las tarifas para los horarios automáticos');
    }

    public function test_auto_publication_can_be_disabled_and_saved_from_rules(): void
    {
        $this->prepareClub(['generacion_automatica' => false]);
        $this->assertSame('desactivada', app(PublicadorHorarios::class)->asegurar()['estado']);
        $this->assertSame(0, Horario::count());
        $admin = User::factory()->create();
        $admin->rol = Rol::Admin;
        $admin->save();
        $rules = ClubSetting::reglas();
        $rules['festivos'] = '';
        $rules['dias_publicacion'] = 30;
        $this->actingAs($admin)->patch(route('club.update'), $rules)->assertSessionHasNoErrors();
        $this->assertFalse((bool) ClubSetting::reglas()['generacion_automatica']);
        $this->assertSame(30, ClubSetting::reglas()['dias_publicacion']);
        $rules['tarifa_dia_id'] = 999999;
        $this->patch(route('club.update'), $rules)->assertSessionHasErrors('tarifa_dia_id');
    }
}
