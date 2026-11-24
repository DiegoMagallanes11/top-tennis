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
            $table->enum('metodo_pago', ['Yape', 'Efectivo', 'Tarjeta'])->change();
            $table->boolean('pago_demo')->default(false)->index();
        });
    }

    public function down(): void
    {
        if (DB::table('reservas')->where('pago_demo', true)->exists()) {
            throw new RuntimeException('No se puede retirar la marca de demostración mientras existan pagos de prueba.');
        }
        // Conservar Tarjeta: reducir el enum perdería compatibilidad con el historial.
        Schema::table('reservas', fn (Blueprint $table) => $table->dropColumn('pago_demo'));
    }
};
