<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('saldo_banco', 15, 2);
            $table->decimal('saldo_sistema', 15, 2);
            $table->decimal('diferencia', 15, 2)->default(0);
            $table->enum('estado', ['abierta', 'cerrada', 'con_diferencias'])->default('abierta');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index(['cuenta_bancaria_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliaciones');
    }
};
