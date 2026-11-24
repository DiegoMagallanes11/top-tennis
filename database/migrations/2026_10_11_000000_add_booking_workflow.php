<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('estado_reserva', 20)->default('pendiente')->index();
            $table->uuid('clave_solicitud')->nullable()->unique();
            $table->string('motivo', 500)->nullable();
            $table->json('contacto')->nullable();
        });
        DB::table('reservas')->where('estado_pago', 'aprobado')->update(['estado_reserva' => 'confirmada']);
        DB::table('reservas')->whereIn('estado_pago', ['anulada', 'cancelado_por_mantenimiento'])->orWhereNotNull('deleted_at')->update(['estado_reserva' => 'cancelada']);
        Schema::create('club_settings', function (Blueprint $table) {
            $table->id();
            $table->json('reglas');
            $table->timestamps();
        });
        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 80);
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['reserva_id', 'created_at']);
        });
        Schema::create('booking_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->restrictOnDelete();
            $table->string('evento', 60);
            $table->string('destinatario');
            $table->json('datos');
            $table->string('estado', 30)->default('pendiente')->index();
            $table->unsignedInteger('intentos')->default(0);
            $table->timestamp('enviado_at')->nullable();
            $table->string('ultimo_error', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_notifications');
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('club_settings');
        Schema::table('reservas', fn (Blueprint $table) => $table->dropColumn(['estado_reserva', 'clave_solicitud', 'motivo', 'contacto']));
    }
};
