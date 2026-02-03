<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmaciones_saldo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->onDelete('cascade');
            $table->decimal('saldo_sistema', 15, 2)->comment('Saldo que tenía el sistema al momento de confirmar');
            $table->decimal('saldo_real', 15, 2)->comment('Saldo real ingresado por el usuario');
            $table->decimal('diferencia', 15, 2)->default(0)->comment('Diferencia entre saldo real y sistema');
            $table->enum('estado', ['confirmado', 'con_diferencia', 'pendiente_ajuste', 'ajustado'])->default('confirmado');
            $table->text('notas')->nullable();
            $table->date('fecha_confirmacion');
            $table->timestamps();

            $table->index(['cuenta_bancaria_id', 'fecha_confirmacion']);
        });

        // Tabla para tracking de cuando el usuario omitió la confirmación
        Schema::create('omisiones_confirmacion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->unique('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omisiones_confirmacion');
        Schema::dropIfExists('confirmaciones_saldo');
    }
};
