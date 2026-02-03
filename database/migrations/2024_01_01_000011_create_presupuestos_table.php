<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('anio');
            $table->integer('mes')->default(0); // 0 = anual, 1-12 = mensual
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->decimal('monto_presupuestado', 15, 2);
            $table->decimal('monto_ejecutado', 15, 2)->default(0);
            $table->decimal('variacion', 15, 2)->default(0);
            $table->decimal('porcentaje_ejecucion', 8, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices
            $table->unique(['anio', 'mes', 'categoria_id', 'tipo']);
            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
