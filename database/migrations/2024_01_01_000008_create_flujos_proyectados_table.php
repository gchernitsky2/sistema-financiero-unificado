<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flujos_proyectados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');

            // Datos principales
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 15, 2);
            $table->date('fecha_proyectada');

            // Datos reales (cuando se cumple)
            $table->date('fecha_real')->nullable();
            $table->decimal('monto_real', 15, 2)->nullable();

            // Estado
            $table->enum('estado', ['pendiente', 'cumplido', 'parcial', 'cancelado', 'vencido'])->default('pendiente');

            // Recurrencia (de PropertyFlow)
            $table->enum('recurrencia', ['unico', 'diario', 'semanal', 'quincenal', 'mensual', 'bimestral', 'trimestral', 'semestral', 'anual'])->default('unico');
            $table->date('fecha_fin_recurrencia')->nullable();

            // Detalles adicionales
            $table->string('beneficiario')->nullable();
            $table->string('referencia')->nullable();
            $table->integer('prioridad')->default(5); // 1-10

            // Vinculación con movimiento real
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');

            // Notas y auditoría
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['fecha_proyectada', 'estado']);
            $table->index(['tipo', 'estado']);
            $table->index(['cuenta_bancaria_id', 'fecha_proyectada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flujos_proyectados');
    }
};
