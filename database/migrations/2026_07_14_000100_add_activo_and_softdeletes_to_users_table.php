<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Estado de cuenta y borrado lógico de usuarios.
// activo: permite desactivar cuentas sin borrarlas (no pueden iniciar sesión).
// softDeletes: "eliminar" conserva el historial de reservas del usuario.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('rol');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->dropSoftDeletes();
        });
    }
};
