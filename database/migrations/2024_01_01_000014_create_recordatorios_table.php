<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['pago', 'prestamo', 'movimiento', 'meta', 'general'])->default('general');

            // Relación polimórfica para vincular con diferentes modelos
            $table->string('recordable_type')->nullable();
            $table->unsignedBigInteger('recordable_id')->nullable();

            // Datos del recordatorio
            $table->string('titulo');
            $table->text('mensaje')->nullable();
            $table->date('fecha_recordatorio');
            $table->time('hora_recordatorio')->nullable();

            // Configuración
            $table->integer('dias_anticipacion')->default(3); // 0-30 días antes
            $table->boolean('repetir')->default(false);
            $table->enum('frecuencia_repeticion', ['diario', 'semanal', 'mensual', 'anual'])->nullable();

            // Estado
            $table->enum('estado', ['pendiente', 'enviado', 'visto', 'descartado'])->default('pendiente');

            // Notificaciones
            $table->boolean('notificar_email')->default(false);
            $table->boolean('notificar_sistema')->default(true);

            // Fechas de seguimiento
            $table->timestamp('fecha_enviado')->nullable();
            $table->timestamp('fecha_visto')->nullable();

            // Auditoría
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices
            $table->index(['recordable_type', 'recordable_id']);
            $table->index(['fecha_recordatorio', 'estado']);
            $table->index(['tipo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
