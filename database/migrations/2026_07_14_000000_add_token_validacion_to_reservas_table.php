<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agrega el token seguro del QR y la marca de "ya usada" (control de acceso).
// token_validacion: cadena aleatoria única que va DENTRO del QR como enlace de verificación.
// usado_at: cuándo el admin marcó el ingreso (evita reutilizar el mismo QR dos veces).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('token_validacion', 64)->nullable()->unique()->after('codigo_validacion');
            $table->dateTime('usado_at')->nullable()->after('expira_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique('reservas_token_validacion_unique');
            $table->dropColumn(['token_validacion', 'usado_at']);
        });
    }
};
