<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Deuda extends Model
{
    use HasFactory;

    protected $table = 'deudas';

    protected $fillable = [
        'tipo',
        'persona_nombre',
        'persona_telefono',
        'persona_email',
        'descripcion',
        'monto_original',
        'monto_pagado',
        'fecha_creacion',
        'fecha_vencimiento',
        'estado',
        'cuenta_bancaria_id',
        'categoria_id',
        'notas',
        'prioridad',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_creacion' => 'date',
        'fecha_vencimiento' => 'date',
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

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoDeuda::class);
    }

    // Scopes
    public function scopePorCobrar($query)
    {
        return $query->where('tipo', 'receivable');
    }

    public function scopePorPagar($query)
    {
        return $query->where('tipo', 'payable');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeParciales($query)
    {
        return $query->where('estado', 'parcial');
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['pendiente', 'parcial', 'vencido']);
    }

    public function scopeProximasVencer($query, int $dias = 7)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->where('fecha_vencimiento', '>=', now())
            ->whereIn('estado', ['pendiente', 'parcial']);
    }

    public function scopeFiltrarPorTipo($query, ?string $tipo)
    {
        if ($tipo && $tipo !== 'all') {
            return $query->where('tipo', $tipo);
        }
        return $query;
    }

    public function scopeFiltrarPorEstado($query, ?string $estado)
    {
        if ($estado && $estado !== 'all') {
            return $query->where('estado', $estado);
        }
        return $query;
    }

    // Métodos
    public function registrarPago(float $monto, ?string $metodoPago = null, ?string $referencia = null, ?string $notas = null): PagoDeuda
    {
        $pago = $this->pagos()->create([
            'monto' => $monto,
            'fecha_pago' => now(),
            'metodo_pago' => $metodoPago,
            'referencia' => $referencia,
            'notas' => $notas,
        ]);

        $this->monto_pagado += $monto;
        $this->actualizarEstado();
        $this->save();

        return $pago;
    }

    public function actualizarEstado(): void
    {
        if ($this->monto_pagado >= $this->monto_original) {
            $this->estado = 'pagado';
        } elseif ($this->monto_pagado > 0) {
            $this->estado = 'parcial';
        } elseif ($this->fecha_vencimiento && $this->fecha_vencimiento->isPast()) {
            $this->estado = 'vencido';
        } else {
            $this->estado = 'pendiente';
        }
    }

    public function marcarComoVencida(): void
    {
        if ($this->estado !== 'pagado' && $this->estado !== 'cancelado') {
            $this->estado = 'vencido';
            $this->save();
        }
    }

    public function cancelar(): void
    {
        $this->estado = 'cancelado';
        $this->save();
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'receivable' => 'Por Cobrar',
            'payable' => 'Por Pagar',
            default => $this->tipo,
        };
    }

    public function getTipoColorAttribute(): string
    {
        return match ($this->tipo) {
            'receivable' => 'green',
            'payable' => 'red',
            default => 'gray',
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'parcial' => 'Pago Parcial',
            'pagado' => 'Pagado',
            'vencido' => 'Vencido',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'yellow',
            'parcial' => 'blue',
            'pagado' => 'green',
            'vencido' => 'red',
            'cancelado' => 'gray',
            default => 'gray',
        };
    }

    public function getPorcentajePagadoAttribute(): float
    {
        if ($this->monto_original == 0) return 0;
        return round(($this->monto_pagado / $this->monto_original) * 100, 2);
    }

    public function getDiasParaVencerAttribute(): ?int
    {
        if (!$this->fecha_vencimiento) return null;
        return now()->startOfDay()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast() && $this->estado !== 'pagado';
    }

    public function getEsUrgenteAttribute(): bool
    {
        if (!$this->fecha_vencimiento) return false;
        $diasParaVencer = $this->dias_para_vencer;
        return $diasParaVencer !== null && $diasParaVencer <= 7 && $diasParaVencer >= 0;
    }

    // Métodos estáticos para estadísticas
    public static function getEstadisticas(?string $tipo = null): array
    {
        $query = static::query();
        if ($tipo && $tipo !== 'all') {
            $query->where('tipo', $tipo);
        }

        return [
            'total' => $query->count(),
            'total_monto' => (clone $query)->sum('monto_original'),
            'total_pagado' => (clone $query)->sum('monto_pagado'),
            'total_pendiente' => (clone $query)->whereIn('estado', ['pendiente', 'parcial', 'vencido'])->sum(\DB::raw('monto_original - monto_pagado')),
            'pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
            'parciales' => (clone $query)->where('estado', 'parcial')->count(),
            'pagadas' => (clone $query)->where('estado', 'pagado')->count(),
            'vencidas' => (clone $query)->where('estado', 'vencido')->count(),
        ];
    }
}
