<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPrestamo extends Model
{
    use HasFactory;

    protected $table = 'pagos_prestamo';

    protected $fillable = [
        'prestamo_id',
        'numero_cuota',
        'fecha_programada',
        'fecha_pago',
        'monto_capital',
        'monto_interes',
        'monto_mora',
        'monto_total',
        'monto_pagado',
        'saldo_capital',
        'saldo_interes',
        'estado',
        'movimiento_id',
        'comprobante',
        'notas',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_pago' => 'date',
        'monto_capital' => 'decimal:2',
        'monto_interes' => 'decimal:2',
        'monto_mora' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_capital' => 'decimal:2',
        'saldo_interes' => 'decimal:2',
    ];

    // Relaciones
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeProximosVencer($query, $dias = 7)
    {
        return $query->where('fecha_programada', '<=', now()->addDays($dias))
                     ->where('fecha_programada', '>=', now())
                     ->where('estado', 'pendiente');
    }

    // Métodos
    public function registrarPago(?float $montoPagado = null, ?string $comprobante = null): ?Movimiento
    {
        $montoPagado = $montoPagado ?? $this->monto_total;

        // Verificar si ya está pagado
        if ($this->estado === 'pagado') {
            return null;
        }

        // Crear movimiento si tiene cuenta asociada
        $movimiento = null;
        if ($this->prestamo->cuenta_bancaria_id) {
            $tipo = $this->prestamo->tipo === 'recibido' ? 'egreso' : 'ingreso';

            $movimiento = Movimiento::create([
                'cuenta_bancaria_id' => $this->prestamo->cuenta_bancaria_id,
                'fecha' => now(),
                'concepto' => "Pago cuota #{$this->numero_cuota} - {$this->prestamo->beneficiario}",
                'monto' => $montoPagado,
                'tipo' => $tipo,
                'beneficiario' => $this->prestamo->beneficiario,
                'referencia' => $this->prestamo->referencia,
                'estado' => 'pendiente',
                'pagado' => true,
                'es_prestamo_socio' => true,
                'user_id' => $this->prestamo->user_id,
            ]);

            $this->movimiento_id = $movimiento->id;
        }

        // Actualizar pago
        $this->monto_pagado = $montoPagado;
        $this->fecha_pago = now();
        $this->estado = $montoPagado >= $this->monto_total ? 'pagado' : 'parcial';

        if ($comprobante) {
            $this->comprobante = $comprobante;
        }

        $this->save();

        // Actualizar saldos del préstamo
        $this->prestamo->actualizarSaldos();

        return $movimiento;
    }

    public function calcularMora(): void
    {
        if ($this->fecha_programada < now() && $this->estado === 'pendiente') {
            $diasVencido = now()->diffInDays($this->fecha_programada);
            // Mora del 2% mensual sobre el saldo
            $this->monto_mora = round(($this->monto_total * 0.02) * ($diasVencido / 30), 2);
            $this->estado = 'vencido';
            $this->save();
        }
    }

    // Accessors
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'pagado' => 'Pagado',
            'parcial' => 'Parcial',
            'vencido' => 'Vencido',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getDiasVencidoAttribute(): int
    {
        if ($this->fecha_programada >= now() || $this->estado === 'pagado') {
            return 0;
        }
        return now()->diffInDays($this->fecha_programada);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->monto_total + $this->monto_mora - $this->monto_pagado;
    }

    public function getColorSemaforoAttribute(): string
    {
        if ($this->estado === 'pagado') return 'green';
        if ($this->estado === 'vencido' || $this->fecha_programada < now()) return 'red';
        if ($this->fecha_programada <= now()->addDays(7)) return 'orange';
        return 'gray';
    }
}
