<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'tipo',
        'color',
        'icono',
        'descripcion',
        'parent_id',
        'orden',
        'activa',
        'es_sistema',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'es_sistema' => 'boolean',
        'orden' => 'integer',
    ];

    // Relaciones
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id');
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

    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeDeIngreso($query)
    {
        return $query->whereIn('tipo', ['ingreso', 'ambos']);
    }

    public function scopeDeEgreso($query)
    {
        return $query->whereIn('tipo', ['egreso', 'ambos']);
    }

    public function scopePrincipales($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'ingreso' => 'Ingreso',
            'egreso' => 'Egreso',
            'ambos' => 'Ambos',
            default => $this->tipo,
        };
    }

    public function getNombreCompletoAttribute(): string
    {
        if ($this->parent) {
            return "{$this->parent->nombre} > {$this->nombre}";
        }
        return $this->nombre;
    }
}
