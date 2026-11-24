<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Índices para acelerar el calendario y la búsqueda de horarios disponibles
// (filtran por cancha + hora de inicio y por estado). Ayuda a la escalabilidad.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->index(['cancha_id', 'hora_inicio'], 'horarios_cancha_hora_idx');
            $table->index('estado', 'horarios_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex('horarios_cancha_hora_idx');
            $table->dropIndex('horarios_estado_idx');
        });
    }
};
