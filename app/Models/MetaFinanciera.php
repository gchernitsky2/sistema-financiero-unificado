<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaFinanciera extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'metas_financieras';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'monto_objetivo',
        'monto_actual',
        'aporte_mensual',
        'fecha_inicio',
        'fecha_objetivo',
        'cuenta_bancaria_id',
        'prestamo_id',
        'estado',
        'prioridad',
        'color',
        'icono',
        'notificar_progreso',
        'user_id',
    ];

    protected $casts = [
        'monto_objetivo' => 'decimal:2',
        'monto_actual' => 'decimal:2',
        'aporte_mensual' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_objetivo' => 'date',
        'prioridad' => 'integer',
        'notificar_progreso' => 'boolean',
    ];

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(AporteMeta::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function scopePausadas($query)
    {
        return $query->where('estado', 'pausada');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeEnRiesgo($query)
    {
        return $query->where('estado', 'activa')
                     ->whereRaw('porcentaje_progreso < porcentaje_tiempo_esperado');
    }

    public function scopeOrdenPrioridad($query)
    {
        return $query->orderBy('prioridad', 'desc')->orderBy('fecha_objetivo');
    }

    // Métodos
    public function registrarAporte(float $monto, ?string $notas = null, ?Movimiento $movimiento = null): AporteMeta
    {
        $aporte = $this->aportes()->create([
            'fecha' => now(),
            'monto' => $monto,
            'notas' => $notas,
            'movimiento_id' => $movimiento?->id,
        ]);

        $this->monto_actual += $monto;

        // Verificar si se completó
        if ($this->monto_actual >= $this->monto_objetivo) {
            $this->estado = 'completada';
        }

        $this->save();

        return $aporte;
    }

    public function cambiarEstado(string $estado): void
    {
        $this->estado = $estado;
        $this->save();
    }

    public function calcularAporteNecesario(): float
    {
        if ($this->estado === 'completada' || $this->monto_actual >= $this->monto_objetivo) {
            return 0;
        }

        $mesesRestantes = $this->meses_restantes;
        if ($mesesRestantes <= 0) {
            return $this->monto_restante;
        }

        return round($this->monto_restante / $mesesRestantes, 2);
    }

    public function getHistorialAportesPorMes(int $meses = 12): array
    {
        return $this->aportes()
            ->selectRaw('YEAR(fecha) as anio, MONTH(fecha) as mes, SUM(monto) as total')
            ->where('fecha', '>=', now()->subMonths($meses))
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->toArray();
    }

    public function getProyeccion(int $meses = 12): array
    {
        $promedioMensual = $this->aportes()
            ->where('fecha', '>=', now()->subMonths(6))
            ->avg('monto') ?? 0;

        $proyeccion = [];
        $montoProyectado = $this->monto_actual;

        for ($i = 1; $i <= $meses; $i++) {
            $montoProyectado += $promedioMensual;
            $proyeccion[] = [
                'mes' => now()->addMonths($i)->format('Y-m'),
                'monto_proyectado' => min($montoProyectado, $this->monto_objetivo),
                'completado' => $montoProyectado >= $this->monto_objetivo,
            ];

            if ($montoProyectado >= $this->monto_objetivo) {
                break;
            }
        }

        return $proyeccion;
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'ahorro' => 'Ahorro',
            'pago_deuda' => 'Pago de Deuda',
            'inversion' => 'Inversión',
            'fondo_emergencia' => 'Fondo de Emergencia',
            'compra' => 'Compra',
            'otro' => 'Otro',
            default => $this->tipo,
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'activa' => 'Activa',
            'pausada' => 'Pausada',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => $this->estado,
        };
    }

    public function getPorcentajeProgresoAttribute(): float
    {
        if ($this->monto_objetivo == 0) return 0;
        return min(100, ($this->monto_actual / $this->monto_objetivo) * 100);
    }

    public function getMontoRestanteAttribute(): float
    {
        return max(0, $this->monto_objetivo - $this->monto_actual);
    }

    public function getDiasRestantesAttribute(): int
    {
        if ($this->fecha_objetivo <= now()) return 0;
        return now()->diffInDays($this->fecha_objetivo);
    }

    public function getMesesRestantesAttribute(): int
    {
        if ($this->fecha_objetivo <= now()) return 0;
        return now()->diffInMonths($this->fecha_objetivo);
    }

    public function getPorcentajeTiempoTranscurridoAttribute(): float
    {
        $totalDias = $this->fecha_inicio->diffInDays($this->fecha_objetivo);
        if ($totalDias == 0) return 100;

        $diasTranscurridos = $this->fecha_inicio->diffInDays(now());
        return min(100, ($diasTranscurridos / $totalDias) * 100);
    }

    public function getEstaEnRiesgoAttribute(): bool
    {
        return $this->estado === 'activa' &&
               $this->porcentaje_progreso < $this->porcentaje_tiempo_transcurrido;
    }

    public function getColorEstadoAttribute(): string
    {
        if ($this->estado === 'completada') return 'green';
        if ($this->estado === 'cancelada') return 'gray';
        if ($this->estado === 'pausada') return 'yellow';
        if ($this->esta_en_riesgo) return 'red';
        return 'blue';
    }

    public function getAporteNecesarioMensualAttribute(): float
    {
        return $this->calcularAporteNecesario();
    }

    public function getMesesParaCompletarAttribute(): ?int
    {
        $promedioMensual = $this->aportes()
            ->where('fecha', '>=', now()->subMonths(6))
            ->avg('monto') ?? 0;

        if ($promedioMensual <= 0) return null;

        return ceil($this->monto_restante / $promedioMensual);
    }
}
