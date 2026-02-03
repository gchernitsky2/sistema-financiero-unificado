<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conciliacion extends Model
{
    use HasFactory;

    protected $table = 'conciliaciones';

    protected $fillable = [
        'cuenta_bancaria_id',
        'fecha_inicio',
        'fecha_fin',
        'saldo_banco',
        'saldo_sistema',
        'diferencia',
        'estado',
        'observaciones',
        'user_id',
        'cerrada_at',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'saldo_banco' => 'decimal:2',
        'saldo_sistema' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'cerrada_at' => 'datetime',
    ];

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    // Scopes
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    // Métodos
    public function calcularDiferencia(): void
    {
        $this->diferencia = $this->saldo_banco - $this->saldo_sistema;
        $this->save();
    }

    public function cerrar(): bool
    {
        if ($this->estado === 'cerrada') {
            return false;
        }

        $this->estado = abs($this->diferencia) < 0.01 ? 'cerrada' : 'con_diferencias';
        $this->cerrada_at = now();
        $this->save();

        // Marcar movimientos como conciliados
        $this->movimientos()->update(['estado' => 'conciliado']);

        return true;
    }

    // Accessors
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'abierta' => 'Abierta',
            'cerrada' => 'Cerrada',
            'con_diferencias' => 'Con Diferencias',
            default => $this->estado,
        };
    }

    public function getTieneDiferenciaAttribute(): bool
    {
        return abs($this->diferencia) >= 0.01;
    }
}
