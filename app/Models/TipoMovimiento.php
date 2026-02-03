<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'tipos_movimiento';

    protected $fillable = [
        'nombre',
        'codigo',
        'naturaleza',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeCargo($query)
    {
        return $query->where('naturaleza', 'cargo');
    }

    public function scopeDeAbono($query)
    {
        return $query->where('naturaleza', 'abono');
    }

    // Accessors
    public function getNaturalezaLabelAttribute(): string
    {
        return $this->naturaleza === 'cargo' ? 'Cargo (Salida)' : 'Abono (Entrada)';
    }
}
