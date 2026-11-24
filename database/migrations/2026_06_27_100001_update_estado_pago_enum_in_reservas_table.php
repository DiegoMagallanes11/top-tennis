<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->enum('estado_pago', ['pendiente', 'aprobado', 'anulada', 'cancelado_por_mantenimiento'])
                ->default('pendiente')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->enum('estado_pago', ['pendiente', 'aprobado', 'anulada'])
                ->default('pendiente')->change();
        });
    }
};
