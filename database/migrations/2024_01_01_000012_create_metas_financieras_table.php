<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_financieras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['ahorro', 'pago_deuda', 'inversion', 'fondo_emergencia', 'compra', 'otro'])->default('ahorro');
            $table->decimal('monto_objetivo', 15, 2);
            $table->decimal('monto_actual', 15, 2)->default(0);
            $table->decimal('aporte_mensual', 15, 2)->nullable(); // Aporte sugerido
            $table->date('fecha_inicio');
            $table->date('fecha_objetivo');
            $table->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->onDelete('set null');
            $table->foreignId('prestamo_id')->nullable()->constrained('prestamos')->onDelete('set null'); // Para metas de pago deuda
            $table->enum('estado', ['activa', 'pausada', 'completada', 'cancelada'])->default('activa');
            $table->integer('prioridad')->default(3); // 1-5
            $table->string('color', 7)->default('#3B82F6'); // Color para UI
            $table->string('icono')->nullable(); // Clase de icono
            $table->boolean('notificar_progreso')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['estado', 'prioridad']);
            $table->index(['fecha_objetivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_financieras');
    }
};
