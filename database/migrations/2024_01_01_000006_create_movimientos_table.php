<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->onDelete('cascade');
            $table->foreignId('tipo_movimiento_id')->nullable()->constrained('tipos_movimiento')->onDelete('set null');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->foreignId('conciliacion_id')->nullable()->constrained('conciliaciones')->onDelete('set null');

            // Datos principales
            $table->date('fecha');
            $table->date('fecha_valor')->nullable(); // Fecha contable
            $table->string('numero_documento')->nullable();
            $table->string('referencia')->nullable();
            $table->string('beneficiario')->nullable();
            $table->text('concepto');

            // Montos
            $table->decimal('monto', 15, 2);
            $table->enum('tipo', ['ingreso', 'egreso']); // ingreso=abono, egreso=cargo
            $table->decimal('saldo_despues', 15, 2)->nullable();

            // IVA
            $table->boolean('tiene_iva')->default(false);
            $table->decimal('porcentaje_iva', 5, 2)->default(16);
            $table->decimal('monto_neto', 15, 2)->nullable();
            $table->decimal('monto_iva', 15, 2)->nullable();

            // Estado y clasificación
            $table->enum('estado', ['pendiente', 'conciliado', 'cancelado'])->default('pendiente');
            $table->boolean('es_urgente')->default(false);
            $table->boolean('pagado')->default(true);

            // Marcas especiales (del sistema PropertyFlow)
            $table->boolean('es_www')->default(false);
            $table->boolean('es_prestamo_socio')->default(false);
            $table->boolean('es_transferencia_interna')->default(false);

            // Archivos y notas
            $table->string('comprobante')->nullable();
            $table->text('notas')->nullable();

            // Auditoría
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['fecha', 'estado']);
            $table->index(['cuenta_bancaria_id', 'fecha']);
            $table->index(['tipo', 'estado']);
            $table->index('numero_documento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
