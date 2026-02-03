<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PagoProgramado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pagos_programados';

    protected $fillable = [
        'cuenta_bancaria_id',
        'categoria_id',
        'tipo',
        'beneficiario',
        'concepto',
        'descripcion',
        'monto',
        'monto_pagado',
        'fecha_programada',
        'fecha_pago',
        'estado',
        'prioridad_manual',
        'prioridad_calculada',
        'dias_para_vencer',
        'recurrencia',
        'fecha_fin_recurrencia',
        'es_urgente',
        'es_recurrente',
        'es_critico',
        'tiene_mora',
        'porcentaje_mora',
        'monto_mora',
        'tipo_pago',
        'categoria_urgencia',
        'numero_factura',
        'numero_contrato',
        'referencia_bancaria',
        'movimiento_id',
        'comprobante',
        'notas',
        'notas_ia',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_programada' => 'date',
        'fecha_pago' => 'date',
        'prioridad_calculada' => 'decimal:2',
        'fecha_fin_recurrencia' => 'date',
        'es_urgente' => 'boolean',
        'es_recurrente' => 'boolean',
        'es_critico' => 'boolean',
        'tiene_mora' => 'boolean',
        'porcentaje_mora' => 'decimal:2',
        'monto_mora' => 'decimal:2',
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

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('es_urgente', true)->orWhere('categoria_urgencia', 'urgente');
    }

    public function scopeCriticos($query)
    {
        return $query->where('es_critico', true)->orWhere('categoria_urgencia', 'critico');
    }

    public function scopeProximosVencer($query, $dias = 7)
    {
        return $query->where('fecha_programada', '<=', now()->addDays($dias))
                     ->where('fecha_programada', '>=', now())
                     ->whereIn('estado', ['pendiente', 'programado']);
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopeOrdenPrioridad($query)
    {
        return $query->orderByDesc('prioridad_calculada')->orderBy('fecha_programada');
    }

    // Métodos
    public function actualizarDiasParaVencer(): void
    {
        $this->dias_para_vencer = now()->diffInDays($this->fecha_programada, false);
        $this->save();
    }

    public function actualizarVencido(): void
    {
        if ($this->fecha_programada < now() && $this->estado === 'pendiente') {
            $this->estado = 'vencido';
            $this->save();
        }
    }

    public function calcularMora(): void
    {
        if ($this->fecha_programada < now() && $this->porcentaje_mora > 0) {
            $diasVencido = now()->diffInDays($this->fecha_programada);
            $this->tiene_mora = true;
            $this->monto_mora = $this->monto * ($this->porcentaje_mora / 100) * ($diasVencido / 30);
            $this->save();
        }
    }

    public function calcularPrioridad(): void
    {
        // Algoritmo IA de priorización
        $puntos = 0;

        // Por días para vencer (más urgente = más puntos)
        $diasVencer = $this->dias_para_vencer ?? now()->diffInDays($this->fecha_programada, false);
        if ($diasVencer < 0) $puntos += 50; // Vencido
        elseif ($diasVencer <= 3) $puntos += 40;
        elseif ($diasVencer <= 7) $puntos += 30;
        elseif ($diasVencer <= 15) $puntos += 20;
        elseif ($diasVencer <= 30) $puntos += 10;

        // Por categoría de urgencia
        $puntos += match ($this->categoria_urgencia) {
            'critico' => 30,
            'urgente' => 20,
            'normal' => 10,
            'diferible' => 5,
            'opcional' => 0,
            default => 10,
        };

        // Por monto (pagos grandes primero)
        if ($this->monto >= 50000) $puntos += 15;
        elseif ($this->monto >= 10000) $puntos += 10;
        elseif ($this->monto >= 1000) $puntos += 5;

        // Por prioridad manual
        if ($this->prioridad_manual) {
            $puntos += $this->prioridad_manual * 2;
        }

        // Por marcas especiales
        if ($this->es_critico) $puntos += 20;
        if ($this->es_urgente) $puntos += 10;
        if ($this->tiene_mora) $puntos += 15;

        $this->prioridad_calculada = min($puntos, 100);
        $this->save();
    }

    public function marcarPagado(?float $montoPagado = null): Movimiento
    {
        $montoPagado = $montoPagado ?? $this->monto;

        // Crear movimiento
        $movimiento = Movimiento::create([
            'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
            'categoria_id' => $this->categoria_id,
            'fecha' => now(),
            'concepto' => $this->concepto,
            'monto' => $montoPagado,
            'tipo' => $this->tipo,
            'beneficiario' => $this->beneficiario,
            'referencia' => $this->referencia_bancaria,
            'estado' => 'pendiente',
            'pagado' => true,
            'user_id' => $this->user_id,
        ]);

        // Actualizar pago
        $this->monto_pagado += $montoPagado;
        $this->movimiento_id = $movimiento->id;
        $this->fecha_pago = now();

        if ($this->monto_pagado >= $this->monto) {
            $this->estado = 'pagado';
        } else {
            $this->estado = 'parcial';
        }

        $this->save();

        // Si es recurrente, crear siguiente
        if ($this->recurrencia !== 'unico' && $this->estado === 'pagado') {
            $this->crearSiguienteRecurrencia();
        }

        return $movimiento;
    }

    public function cancelar(): void
    {
        $this->estado = 'cancelado';
        $this->save();
    }

    protected function crearSiguienteRecurrencia(): void
    {
        $siguienteFecha = match ($this->recurrencia) {
            'diario' => $this->fecha_programada->addDay(),
            'semanal' => $this->fecha_programada->addWeek(),
            'quincenal' => $this->fecha_programada->addDays(15),
            'mensual' => $this->fecha_programada->addMonth(),
            'bimestral' => $this->fecha_programada->addMonths(2),
            'trimestral' => $this->fecha_programada->addMonths(3),
            'semestral' => $this->fecha_programada->addMonths(6),
            'anual' => $this->fecha_programada->addYear(),
            default => null,
        };

        if ($siguienteFecha && (!$this->fecha_fin_recurrencia || $siguienteFecha <= $this->fecha_fin_recurrencia)) {
            $nuevo = $this->replicate();
            $nuevo->fecha_programada = $siguienteFecha;
            $nuevo->estado = 'pendiente';
            $nuevo->monto_pagado = 0;
            $nuevo->fecha_pago = null;
            $nuevo->movimiento_id = null;
            $nuevo->tiene_mora = false;
            $nuevo->monto_mora = 0;
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
            'programado' => 'Programado',
            'pagado' => 'Pagado',
            'parcial' => 'Parcial',
            'vencido' => 'Vencido',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getCategoriaUrgenciaLabelAttribute(): string
    {
        return match ($this->categoria_urgencia) {
            'critico' => 'Crítico',
            'urgente' => 'Urgente',
            'normal' => 'Normal',
            'diferible' => 'Diferible',
            'opcional' => 'Opcional',
            default => $this->categoria_urgencia,
        };
    }

    public function getColorSemaforoAttribute(): string
    {
        $dias = $this->dias_para_vencer ?? now()->diffInDays($this->fecha_programada, false);

        if ($dias < 0 || $this->estado === 'vencido') return 'red';
        if ($dias <= 7) return 'orange';
        return 'green';
    }

    public function getMontoTotalConMoraAttribute(): float
    {
        return $this->monto + $this->monto_mora;
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->monto - $this->monto_pagado;
    }
}
