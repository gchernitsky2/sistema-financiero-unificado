<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_id')->constrained('bancos')->onDelete('cascade');
            $table->string('nombre');
            $table->string('numero_cuenta')->nullable();
            $table->string('clabe')->nullable();
            $table->enum('tipo', ['banco', 'efectivo', 'tarjeta', 'inversion', 'otros'])->default('banco');
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_actual', 15, 2)->default(0);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->nullable(); // Color hex para UI
            $table->timestamps();

            $table->index(['banco_id', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
