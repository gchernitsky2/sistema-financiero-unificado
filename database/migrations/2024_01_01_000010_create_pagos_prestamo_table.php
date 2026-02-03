<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_prestamo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->onDelete('cascade');

            // Datos de la cuota (tabla de amortización de FlujoCash)
            $table->integer('numero_cuota');
            $table->date('fecha_programada');
            $table->date('fecha_pago')->nullable();

            // Desglose de montos
            $table->decimal('monto_capital', 15, 2);
            $table->decimal('monto_interes', 15, 2)->default(0);
            $table->decimal('monto_mora', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0);

            // Saldos después del pago
            $table->decimal('saldo_capital', 15, 2);
            $table->decimal('saldo_interes', 15, 2)->default(0);

            // Estado
            $table->enum('estado', ['pendiente', 'pagado', 'parcial', 'vencido', 'cancelado'])->default('pendiente');

            // Vinculación con movimiento
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');

            // Archivos y notas
            $table->string('comprobante')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['prestamo_id', 'numero_cuota']);
            $table->index(['fecha_programada', 'estado']);
            $table->unique(['prestamo_id', 'numero_cuota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_prestamo');
    }
};
