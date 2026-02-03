<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->onDelete('set null');

            // Tipo y datos principales
            $table->enum('tipo', ['otorgado', 'recibido']); // otorgado = prestamos a otros, recibido = nos prestan
            $table->string('beneficiario'); // A quién o de quién
            $table->text('descripcion')->nullable();

            // Configuración financiera (de FlujoCash - amortización completa)
            $table->decimal('monto_principal', 15, 2);
            $table->decimal('tasa_interes', 8, 4)->default(0); // Porcentaje anual
            $table->enum('tipo_interes', ['simple', 'compuesto'])->default('simple');
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');

            // Pagos
            $table->enum('frecuencia_pago', ['unico', 'semanal', 'quincenal', 'mensual', 'bimestral', 'trimestral', 'semestral', 'anual'])->default('mensual');
            $table->integer('numero_pagos')->default(1);
            $table->decimal('monto_cuota', 15, 2)->nullable(); // Calculado automáticamente

            // Saldos y seguimiento
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->decimal('interes_pagado', 15, 2)->default(0);
            $table->decimal('saldo_pendiente', 15, 2)->nullable();
            $table->date('ultimo_pago')->nullable();
            $table->date('proximo_pago')->nullable();

            // Estado
            $table->enum('estado', ['activo', 'pagado', 'vencido', 'cancelado', 'en_mora'])->default('activo');
            $table->boolean('es_urgente')->default(false);

            // Referencias
            $table->string('referencia')->nullable();
            $table->string('numero_contrato')->nullable();

            // Notas y auditoría
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['tipo', 'estado']);
            $table->index(['beneficiario']);
            $table->index(['fecha_vencimiento', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
