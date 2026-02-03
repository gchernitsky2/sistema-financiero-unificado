<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aportes_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_financiera_id')->constrained('metas_financieras')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('monto', 15, 2);
            $table->text('notas')->nullable();
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');
            $table->timestamps();

            $table->index(['meta_financiera_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aportes_meta');
    }
};
