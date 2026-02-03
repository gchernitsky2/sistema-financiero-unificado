<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlujoProyectado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flujos_proyectados';

    protected $fillable = [
        'cuenta_bancaria_id',
        'categoria_id',
        'tipo',
        'concepto',
        'descripcion',
        'monto',
        'fecha_proyectada',
        'fecha_real',
        'monto_real',
        'estado',
        'recurrencia',
        'fecha_fin_recurrencia',
        'beneficiario',
        'referencia',
        'prioridad',
        'movimiento_id',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_proyectada' => 'date',
        'fecha_real' => 'date',
        'monto_real' => 'decimal:2',
        'fecha_fin_recurrencia' => 'date',
        'prioridad' => 'integer',
    ];

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
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

    public function scopeCumplidos($query)
    {
        return $query->where('estado', 'cumplido');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopeDelMes($query, $mes = null, $anio = null)
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;
        return $query->whereMonth('fecha_proyectada', $mes)
                     ->whereYear('fecha_proyectada', $anio);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_proyectada', [$desde, $hasta]);
    }

    public function scopeProximosDias($query, $dias = 30)
    {
        return $query->whereBetween('fecha_proyectada', [now(), now()->addDays($dias)]);
    }

    // Métodos
    public function actualizarVencido(): void
    {
        if ($this->fecha_proyectada < now() && $this->estado === 'pendiente') {
            $this->estado = 'vencido';
            $this->save();
        }
    }

    public function marcarCumplido(?float $montoReal = null, ?Movimiento $movimiento = null): void
    {
        $this->estado = 'cumplido';
        $this->fecha_real = now();
        $this->monto_real = $montoReal ?? $this->monto;

        if ($movimiento) {
            $this->movimiento_id = $movimiento->id;
        }

        $this->save();

        // Si es recurrente, crear siguiente
        if ($this->recurrencia !== 'unico') {
            $this->crearSiguienteRecurrencia();
        }
    }

    public function marcarParcial(float $montoReal, ?Movimiento $movimiento = null): void
    {
        $this->estado = 'parcial';
        $this->fecha_real = now();
        $this->monto_real = $montoReal;

        if ($movimiento) {
            $this->movimiento_id = $movimiento->id;
        }

        $this->save();
    }

    protected function crearSiguienteRecurrencia(): void
    {
        $siguienteFecha = match ($this->recurrencia) {
            'diario' => $this->fecha_proyectada->addDay(),
            'semanal' => $this->fecha_proyectada->addWeek(),
            'quincenal' => $this->fecha_proyectada->addDays(15),
            'mensual' => $this->fecha_proyectada->addMonth(),
            'bimestral' => $this->fecha_proyectada->addMonths(2),
            'trimestral' => $this->fecha_proyectada->addMonths(3),
            'semestral' => $this->fecha_proyectada->addMonths(6),
            'anual' => $this->fecha_proyectada->addYear(),
            default => null,
        };

        if ($siguienteFecha && (!$this->fecha_fin_recurrencia || $siguienteFecha <= $this->fecha_fin_recurrencia)) {
            $nuevo = $this->replicate();
            $nuevo->fecha_proyectada = $siguienteFecha;
            $nuevo->estado = 'pendiente';
            $nuevo->fecha_real = null;
            $nuevo->monto_real = null;
            $nuevo->movimiento_id = null;
            $nuevo->save();
        }
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'ingreso' ? 'Ingreso' : 'Egreso';
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'cumplido' => 'Cumplido',
            'parcial' => 'Parcial',
            'cancelado' => 'Cancelado',
            'vencido' => 'Vencido',
            default => $this->estado,
        };
    }

    public function getRecurrenciaLabelAttribute(): string
    {
        return match ($this->recurrencia) {
            'unico' => 'Único',
            'diario' => 'Diario',
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            'bimestral' => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => $this->recurrencia,
        };
    }

    public function getVariacionAttribute(): ?float
    {
        if ($this->monto_real === null) return null;
        return $this->monto_real - $this->monto;
    }

    public function getPorcentajeVariacionAttribute(): ?float
    {
        if ($this->monto_real === null || $this->monto == 0) return null;
        return (($this->monto_real - $this->monto) / $this->monto) * 100;
    }
}
