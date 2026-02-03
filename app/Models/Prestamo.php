<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestamo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cuenta_bancaria_id',
        'tipo',
        'beneficiario',
        'descripcion',
        'monto_principal',
        'tasa_interes',
        'tipo_interes',
        'fecha_inicio',
        'fecha_vencimiento',
        'frecuencia_pago',
        'numero_pagos',
        'monto_cuota',
        'monto_pagado',
        'interes_pagado',
        'saldo_pendiente',
        'ultimo_pago',
        'proximo_pago',
        'estado',
        'es_urgente',
        'referencia',
        'numero_contrato',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'monto_principal' => 'decimal:2',
        'tasa_interes' => 'decimal:4',
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'interes_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'ultimo_pago' => 'date',
        'proximo_pago' => 'date',
        'es_urgente' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prestamo $prestamo) {
            $prestamo->saldo_pendiente = $prestamo->monto_principal;
            $prestamo->calcularMontoCuota();
        });
    }

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoPrestamo::class);
    }

    public function metasFinancieras(): HasMany
    {
        return $this->hasMany(MetaFinanciera::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeEnMora($query)
    {
        return $query->where('estado', 'en_mora');
    }

    public function scopeOtorgados($query)
    {
        return $query->where('tipo', 'otorgado');
    }

    public function scopeRecibidos($query)
    {
        return $query->where('tipo', 'recibido');
    }

    // Métodos de cálculo financiero (de FlujoCash)
    public function calcularMontoCuota(): void
    {
        if ($this->numero_pagos <= 0) {
            $this->monto_cuota = $this->monto_principal;
            return;
        }

        if ($this->tasa_interes <= 0) {
            // Sin interés
            $this->monto_cuota = $this->monto_principal / $this->numero_pagos;
            return;
        }

        // Convertir tasa anual a tasa por período
        $periodosPorAnio = match ($this->frecuencia_pago) {
            'semanal' => 52,
            'quincenal' => 26,
            'mensual' => 12,
            'bimestral' => 6,
            'trimestral' => 4,
            'semestral' => 2,
            'anual' => 1,
            default => 12,
        };

        $tasaPeriodo = ($this->tasa_interes / 100) / $periodosPorAnio;

        if ($this->tipo_interes === 'simple') {
            // Interés simple
            $interesTotal = $this->monto_principal * ($this->tasa_interes / 100) * ($this->numero_pagos / $periodosPorAnio);
            $this->monto_cuota = ($this->monto_principal + $interesTotal) / $this->numero_pagos;
        } else {
            // Sistema francés (interés compuesto)
            $factor = pow(1 + $tasaPeriodo, $this->numero_pagos);
            $this->monto_cuota = $this->monto_principal * ($tasaPeriodo * $factor) / ($factor - 1);
        }

        $this->monto_cuota = round($this->monto_cuota, 2);
    }

    public function generarTablaAmortizacion(): void
    {
        // Eliminar pagos existentes que no se han realizado
        $this->pagos()->where('estado', 'pendiente')->delete();

        $saldoCapital = $this->monto_principal;
        $fechaPago = $this->fecha_inicio;

        $periodosPorAnio = match ($this->frecuencia_pago) {
            'semanal' => 52,
            'quincenal' => 26,
            'mensual' => 12,
            'bimestral' => 6,
            'trimestral' => 4,
            'semestral' => 2,
            'anual' => 1,
            default => 12,
        };

        $tasaPeriodo = ($this->tasa_interes / 100) / $periodosPorAnio;

        for ($i = 1; $i <= $this->numero_pagos; $i++) {
            $fechaPago = $this->getSiguienteFechaPago($fechaPago);

            $montoInteres = round($saldoCapital * $tasaPeriodo, 2);
            $montoCapital = round($this->monto_cuota - $montoInteres, 2);

            // Ajustar última cuota
            if ($i === $this->numero_pagos) {
                $montoCapital = $saldoCapital;
            }

            $saldoCapital -= $montoCapital;

            PagoPrestamo::create([
                'prestamo_id' => $this->id,
                'numero_cuota' => $i,
                'fecha_programada' => $fechaPago,
                'monto_capital' => $montoCapital,
                'monto_interes' => $montoInteres,
                'monto_total' => $montoCapital + $montoInteres,
                'saldo_capital' => max(0, $saldoCapital),
                'estado' => 'pendiente',
            ]);
        }

        // Actualizar próximo pago
        $primerPago = $this->pagos()->where('estado', 'pendiente')->orderBy('numero_cuota')->first();
        if ($primerPago) {
            $this->proximo_pago = $primerPago->fecha_programada;
            $this->save();
        }
    }

    protected function getSiguienteFechaPago($fechaActual)
    {
        return match ($this->frecuencia_pago) {
            'semanal' => $fechaActual->copy()->addWeek(),
            'quincenal' => $fechaActual->copy()->addDays(15),
            'mensual' => $fechaActual->copy()->addMonth(),
            'bimestral' => $fechaActual->copy()->addMonths(2),
            'trimestral' => $fechaActual->copy()->addMonths(3),
            'semestral' => $fechaActual->copy()->addMonths(6),
            'anual' => $fechaActual->copy()->addYear(),
            'unico' => $this->fecha_vencimiento,
            default => $fechaActual->copy()->addMonth(),
        };
    }

    public function actualizarSaldos(): void
    {
        $pagosRealizados = $this->pagos()->where('estado', 'pagado')->get();

        $this->monto_pagado = $pagosRealizados->sum('monto_pagado');
        $this->interes_pagado = $pagosRealizados->sum('monto_interes');
        $this->saldo_pendiente = $this->monto_principal - $pagosRealizados->sum('monto_capital');

        $ultimoPago = $pagosRealizados->sortByDesc('fecha_pago')->first();
        $this->ultimo_pago = $ultimoPago?->fecha_pago;

        $proximoPago = $this->pagos()->where('estado', 'pendiente')->orderBy('numero_cuota')->first();
        $this->proximo_pago = $proximoPago?->fecha_programada;

        // Verificar si está en mora
        if ($proximoPago && $proximoPago->fecha_programada < now()->subDays(30)) {
            $this->estado = 'en_mora';
        }

        // Verificar si está completamente pagado
        if ($this->saldo_pendiente <= 0.01) {
            $this->estado = 'pagado';
            $this->saldo_pendiente = 0;
        }

        $this->save();
    }

    public function liquidar(): void
    {
        $this->estado = 'pagado';
        $this->saldo_pendiente = 0;
        $this->save();

        // Cancelar pagos pendientes
        $this->pagos()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
    }

    public function cancelar(): void
    {
        $this->estado = 'cancelado';
        $this->save();

        $this->pagos()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'otorgado' ? 'Préstamo Otorgado' : 'Préstamo Recibido';
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'activo' => 'Activo',
            'pagado' => 'Pagado',
            'vencido' => 'Vencido',
            'cancelado' => 'Cancelado',
            'en_mora' => 'En Mora',
            default => $this->estado,
        };
    }

    public function getFrecuenciaPagoLabelAttribute(): string
    {
        return match ($this->frecuencia_pago) {
            'unico' => 'Único',
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            'bimestral' => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => $this->frecuencia_pago,
        };
    }

    public function getTotalAPagarAttribute(): float
    {
        return $this->pagos()->sum('monto_total');
    }

    public function getInteresTotalAttribute(): float
    {
        return $this->total_a_pagar - $this->monto_principal;
    }

    public function getPorcentajePagadoAttribute(): float
    {
        if ($this->monto_principal == 0) return 0;
        return ($this->monto_pagado / $this->monto_principal) * 100;
    }

    public function getCuotasPendientesAttribute(): int
    {
        return $this->pagos()->whereIn('estado', ['pendiente', 'vencido'])->count();
    }

    public function getCuotasPagadasAttribute(): int
    {
        return $this->pagos()->where('estado', 'pagado')->count();
    }
}
