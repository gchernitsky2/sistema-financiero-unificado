<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cuenta_bancaria_id',
        'tipo_movimiento_id',
        'categoria_id',
        'conciliacion_id',
        'fecha',
        'fecha_valor',
        'numero_documento',
        'referencia',
        'beneficiario',
        'concepto',
        'monto',
        'tipo',
        'clasificacion',
        'saldo_despues',
        'tiene_iva',
        'porcentaje_iva',
        'monto_neto',
        'monto_iva',
        'estado',
        'es_urgente',
        'pagado',
        'es_www',
        'es_prestamo_socio',
        'es_transferencia_interna',
        'comprobante',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_valor' => 'date',
        'monto' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'tiene_iva' => 'boolean',
        'porcentaje_iva' => 'decimal:2',
        'monto_neto' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'es_urgente' => 'boolean',
        'pagado' => 'boolean',
        'es_www' => 'boolean',
        'es_prestamo_socio' => 'boolean',
        'es_transferencia_interna' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Actualizar saldo de cuenta después de crear/actualizar
        static::saved(function (Movimiento $movimiento) {
            if ($movimiento->estado !== 'cancelado') {
                $movimiento->cuentaBancaria->recalcularSaldo();
            }
        });

        static::deleted(function (Movimiento $movimiento) {
            $movimiento->cuentaBancaria->recalcularSaldo();
        });
    }

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimiento::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function conciliacion(): BelongsTo
    {
        return $this->belongsTo(Conciliacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aporteMeta(): HasMany
    {
        return $this->hasMany(AporteMeta::class);
    }

    public function pagosPrestamo(): HasMany
    {
        return $this->hasMany(PagoPrestamo::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeConciliados($query)
    {
        return $query->where('estado', 'conciliado');
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('es_urgente', true);
    }

    public function scopeReales($query)
    {
        return $query->where('clasificacion', 'real');
    }

    public function scopeProyectados($query)
    {
        return $query->where('clasificacion', 'proyectado');
    }

    public function scopeProgramados($query)
    {
        return $query->where('clasificacion', 'programado');
    }

    public function scopeDelMes($query, $mes = null, $anio = null)
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;
        return $query->whereMonth('fecha', $mes)->whereYear('fecha', $anio);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    // Métodos
    public function calcularIva(): void
    {
        if ($this->tiene_iva && $this->porcentaje_iva > 0) {
            $factor = 1 + ($this->porcentaje_iva / 100);
            $this->monto_neto = round($this->monto / $factor, 2);
            $this->monto_iva = $this->monto - $this->monto_neto;
        } else {
            $this->monto_neto = $this->monto;
            $this->monto_iva = 0;
        }
    }

    public function marcarComoPagado(): void
    {
        $this->pagado = true;
        $this->save();
    }

    public function conciliar(Conciliacion $conciliacion): void
    {
        $this->conciliacion_id = $conciliacion->id;
        $this->estado = 'conciliado';
        $this->save();
    }

    public function desconciliar(): void
    {
        $this->conciliacion_id = null;
        $this->estado = 'pendiente';
        $this->save();
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
            'conciliado' => 'Conciliado',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getClasificacionLabelAttribute(): string
    {
        return match ($this->clasificacion) {
            'real' => 'Real',
            'proyectado' => 'Proyectado',
            'programado' => 'Programado',
            default => $this->clasificacion ?? 'Real',
        };
    }

    public function getEsProyectadoAttribute(): bool
    {
        return in_array($this->clasificacion, ['proyectado', 'programado']);
    }

    public function getMontoFormateadoAttribute(): string
    {
        $signo = $this->tipo === 'ingreso' ? '+' : '-';
        return $signo . ' $' . number_format($this->monto, 2);
    }
}
