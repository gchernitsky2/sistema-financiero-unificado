<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // Clasificación del movimiento: real (ejecutado), proyectado, programado
            $table->enum('clasificacion', ['real', 'proyectado', 'programado'])->default('real')->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn('clasificacion');
        });
    }
};
