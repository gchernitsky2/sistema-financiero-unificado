<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Recordatorio extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'recordable_type',
        'recordable_id',
        'titulo',
        'mensaje',
        'fecha_recordatorio',
        'hora_recordatorio',
        'dias_anticipacion',
        'repetir',
        'frecuencia_repeticion',
        'estado',
        'notificar_email',
        'notificar_sistema',
        'fecha_enviado',
        'fecha_visto',
        'user_id',
    ];

    protected $casts = [
        'fecha_recordatorio' => 'date',
        'hora_recordatorio' => 'datetime:H:i',
        'dias_anticipacion' => 'integer',
        'repetir' => 'boolean',
        'notificar_email' => 'boolean',
        'notificar_sistema' => 'boolean',
        'fecha_enviado' => 'datetime',
        'fecha_visto' => 'datetime',
    ];

    // Relaciones
    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEnviados($query)
    {
        return $query->where('estado', 'enviado');
    }

    public function scopeVistos($query)
    {
        return $query->where('estado', 'visto');
    }

    public function scopeProximosVencer($query, $dias = 7)
    {
        return $query->where('fecha_recordatorio', '<=', now()->addDays($dias))
                     ->where('estado', 'pendiente');
    }

    public function scopeVencidos($query)
    {
        return $query->where('fecha_recordatorio', '<', now())
                     ->where('estado', 'pendiente');
    }

    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha_recordatorio', now());
    }

    // Métodos
    public function marcarVisto(): void
    {
        $this->estado = 'visto';
        $this->fecha_visto = now();
        $this->save();

        // Si es repetitivo, crear siguiente
        if ($this->repetir && $this->frecuencia_repeticion) {
            $this->crearSiguiente();
        }
    }

    public function descartar(): void
    {
        $this->estado = 'descartado';
        $this->save();
    }

    public function marcarEnviado(): void
    {
        $this->estado = 'enviado';
        $this->fecha_enviado = now();
        $this->save();
    }

    protected function crearSiguiente(): void
    {
        $siguienteFecha = match ($this->frecuencia_repeticion) {
            'diario' => $this->fecha_recordatorio->addDay(),
            'semanal' => $this->fecha_recordatorio->addWeek(),
            'mensual' => $this->fecha_recordatorio->addMonth(),
            'anual' => $this->fecha_recordatorio->addYear(),
            default => null,
        };

        if ($siguienteFecha) {
            $nuevo = $this->replicate();
            $nuevo->fecha_recordatorio = $siguienteFecha;
            $nuevo->estado = 'pendiente';
            $nuevo->fecha_enviado = null;
            $nuevo->fecha_visto = null;
            $nuevo->save();
        }
    }

    public static function generarAutomaticos(): int
    {
        $creados = 0;

        // Recordatorios para pagos programados próximos
        $pagosProgramados = PagoProgramado::whereIn('estado', ['pendiente', 'programado'])
            ->where('fecha_programada', '<=', now()->addDays(10))
            ->where('fecha_programada', '>=', now())
            ->get();

        foreach ($pagosProgramados as $pago) {
            $existe = static::where('recordable_type', PagoProgramado::class)
                          ->where('recordable_id', $pago->id)
                          ->where('estado', 'pendiente')
                          ->exists();

            if (!$existe) {
                static::create([
                    'tipo' => 'pago',
                    'recordable_type' => PagoProgramado::class,
                    'recordable_id' => $pago->id,
                    'titulo' => "Pago próximo: {$pago->concepto}",
                    'mensaje' => "Tienes un pago programado de $" . number_format($pago->monto, 2) . " para el " . $pago->fecha_programada->format('d/m/Y'),
                    'fecha_recordatorio' => $pago->fecha_programada->subDays(3),
                    'dias_anticipacion' => 3,
                    'estado' => 'pendiente',
                    'notificar_sistema' => true,
                    'user_id' => $pago->user_id,
                ]);
                $creados++;
            }
        }

        // Recordatorios para préstamos con cuota próxima
        $pagosPrestamo = PagoPrestamo::where('estado', 'pendiente')
            ->where('fecha_programada', '<=', now()->addDays(10))
            ->where('fecha_programada', '>=', now())
            ->get();

        foreach ($pagosPrestamo as $pagoPrestamo) {
            $existe = static::where('recordable_type', PagoPrestamo::class)
                          ->where('recordable_id', $pagoPrestamo->id)
                          ->where('estado', 'pendiente')
                          ->exists();

            if (!$existe) {
                static::create([
                    'tipo' => 'prestamo',
                    'recordable_type' => PagoPrestamo::class,
                    'recordable_id' => $pagoPrestamo->id,
                    'titulo' => "Cuota #{$pagoPrestamo->numero_cuota} de préstamo",
                    'mensaje' => "Tienes una cuota de préstamo de $" . number_format($pagoPrestamo->monto_total, 2) . " para el " . $pagoPrestamo->fecha_programada->format('d/m/Y'),
                    'fecha_recordatorio' => $pagoPrestamo->fecha_programada->subDays(3),
                    'dias_anticipacion' => 3,
                    'estado' => 'pendiente',
                    'notificar_sistema' => true,
                    'user_id' => $pagoPrestamo->prestamo->user_id,
                ]);
                $creados++;
            }
        }

        return $creados;
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'pago' => 'Pago Programado',
            'prestamo' => 'Préstamo',
            'movimiento' => 'Movimiento',
            'meta' => 'Meta Financiera',
            'general' => 'General',
            default => $this->tipo,
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'enviado' => 'Enviado',
            'visto' => 'Visto',
            'descartado' => 'Descartado',
            default => $this->estado,
        };
    }

    public function getFrecuenciaRepeticionLabelAttribute(): ?string
    {
        if (!$this->frecuencia_repeticion) return null;

        return match ($this->frecuencia_repeticion) {
            'diario' => 'Diario',
            'semanal' => 'Semanal',
            'mensual' => 'Mensual',
            'anual' => 'Anual',
            default => $this->frecuencia_repeticion,
        };
    }

    public function getEsVencidoAttribute(): bool
    {
        return $this->fecha_recordatorio < now() && $this->estado === 'pendiente';
    }

    public function getDiasParaVencerAttribute(): int
    {
        return now()->diffInDays($this->fecha_recordatorio, false);
    }

    public function getColorAttribute(): string
    {
        if ($this->es_vencido) return 'red';
        if ($this->dias_para_vencer <= 3) return 'orange';
        return 'blue';
    }
}
