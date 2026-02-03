<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_programados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');

            // Datos principales
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('beneficiario')->nullable();
            $table->text('concepto');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->date('fecha_programada');
            $table->date('fecha_pago')->nullable();

            // Estado y prioridad (combinando PropertyFlow IA + FlujoCash)
            $table->enum('estado', ['pendiente', 'programado', 'pagado', 'parcial', 'vencido', 'cancelado'])->default('pendiente');
            $table->integer('prioridad_manual')->nullable(); // 1-10
            $table->decimal('prioridad_calculada', 5, 2)->nullable(); // IA
            $table->integer('dias_para_vencer')->nullable();

            // Recurrencia (de FlujoCash)
            $table->enum('recurrencia', ['unico', 'diario', 'semanal', 'quincenal', 'mensual', 'bimestral', 'trimestral', 'semestral', 'anual'])->default('unico');
            $table->date('fecha_fin_recurrencia')->nullable();

            // Características especiales (de PropertyFlow IA)
            $table->boolean('es_urgente')->default(false);
            $table->boolean('es_recurrente')->default(false);
            $table->boolean('es_critico')->default(false);

            // Mora (de PropertyFlow)
            $table->boolean('tiene_mora')->default(false);
            $table->decimal('porcentaje_mora', 5, 2)->default(0);
            $table->decimal('monto_mora', 15, 2)->default(0);

            // Clasificación IA (de PropertyFlow)
            $table->enum('tipo_pago', ['fijo', 'variable', 'estimado'])->default('fijo');
            $table->enum('categoria_urgencia', ['critico', 'urgente', 'normal', 'diferible', 'opcional'])->default('normal');

            // Referencias
            $table->string('numero_factura')->nullable();
            $table->string('numero_contrato')->nullable();
            $table->string('referencia_bancaria')->nullable();
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');

            // Archivos y notas
            $table->string('comprobante')->nullable();
            $table->text('notas')->nullable();
            $table->text('notas_ia')->nullable(); // Sugerencias IA

            // Auditoría
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['fecha_programada', 'estado']);
            $table->index(['prioridad_calculada', 'estado']);
            $table->index(['categoria_urgencia', 'estado']);
            $table->index(['cuenta_bancaria_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_programados');
    }
};
