<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deudas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['receivable', 'payable'])->comment('receivable = por cobrar, payable = por pagar');
            $table->string('persona_nombre')->comment('Nombre de la persona/empresa');
            $table->string('persona_telefono')->nullable();
            $table->string('persona_email')->nullable();
            $table->text('descripcion');
            $table->decimal('monto_original', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->decimal('monto_pendiente', 15, 2)->storedAs('monto_original - monto_pagado');
            $table->date('fecha_creacion');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'parcial', 'pagado', 'vencido', 'cancelado'])->default('pendiente');
            $table->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->integer('prioridad')->default(0)->comment('1=alta, 0=normal, -1=baja');
            $table->timestamps();

            $table->index(['tipo', 'estado']);
            $table->index('fecha_vencimiento');
            $table->index('persona_nombre');
        });

        // Tabla para pagos parciales de deudas
        Schema::create('pagos_deuda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deuda_id')->constrained('deudas')->onDelete('cascade');
            $table->decimal('monto', 15, 2);
            $table->date('fecha_pago');
            $table->string('metodo_pago')->nullable()->comment('efectivo, transferencia, cheque, etc');
            $table->string('referencia')->nullable()->comment('Número de referencia o comprobante');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['deuda_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_deuda');
        Schema::dropIfExists('deudas');
    }
};
