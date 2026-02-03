<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banco extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'swift',
        'direccion',
        'telefono',
        'email',
        'sitio_web',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function cuentas(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class);
    }

    public function cuentasActivas(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class)->where('activa', true);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Accessors
    public function getSaldoTotalAttribute(): float
    {
        return $this->cuentasActivas()->sum('saldo_actual');
    }
}
