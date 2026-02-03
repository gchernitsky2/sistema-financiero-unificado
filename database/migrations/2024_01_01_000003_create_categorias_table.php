<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->nullable();
            $table->enum('tipo', ['ingreso', 'egreso', 'ambos'])->default('ambos');
            $table->string('color', 7)->default('#6B7280'); // Color hex
            $table->string('icono')->nullable(); // Clase de icono
            $table->text('descripcion')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->boolean('es_sistema')->default(false); // Categorías del sistema
            $table->timestamps();

            $table->index(['tipo', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
