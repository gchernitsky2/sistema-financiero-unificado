<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'banco_id',
        'nombre',
        'numero_cuenta',
        'clabe',
        'tipo',
        'moneda',
        'saldo_inicial',
        'saldo_actual',
        'es_principal',
        'activa',
        'descripcion',
        'color',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'es_principal' => 'boolean',
        'activa' => 'boolean',
    ];

    // Relaciones
    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function pagosProgramados(): HasMany
    {
        return $this->hasMany(PagoProgramado::class);
    }

    public function flujosProyectados(): HasMany
    {
        return $this->hasMany(FlujoProyectado::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function conciliaciones(): HasMany
    {
        return $this->hasMany(Conciliacion::class);
    }

    public function metasFinancieras(): HasMany
    {
        return $this->hasMany(MetaFinanciera::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopePrincipales($query)
    {
        return $query->where('es_principal', true);
    }

    // Métodos
    public function recalcularSaldo(): void
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->where('estado', '!=', 'cancelado')->sum('monto');
        $egresos = $this->movimientos()->where('tipo', 'egreso')->where('estado', '!=', 'cancelado')->sum('monto');
        $this->saldo_actual = $this->saldo_inicial + $ingresos - $egresos;
        $this->save();
    }

    public function marcarComoPrincipal(): void
    {
        // Quitar principal de otras cuentas
        static::where('es_principal', true)->update(['es_principal' => false]);
        $this->es_principal = true;
        $this->save();
    }

    // Accessors
    public function getNombreCompletoAttribute(): string
    {
        return $this->banco ? "{$this->banco->nombre} - {$this->nombre}" : $this->nombre;
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'banco' => 'Banco',
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'inversion' => 'Inversión',
            'otros' => 'Otros',
            default => $this->tipo,
        };
    }
}
