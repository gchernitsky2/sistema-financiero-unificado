<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfirmacionSaldo extends Model
{
    use HasFactory;

    protected $table = 'confirmaciones_saldo';

    protected $fillable = [
        'cuenta_bancaria_id',
        'saldo_sistema',
        'saldo_real',
        'diferencia',
        'estado',
        'notas',
        'fecha_confirmacion',
    ];

    protected $casts = [
        'saldo_sistema' => 'decimal:2',
        'saldo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fecha_confirmacion' => 'date',
    ];

    // Relaciones
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    // Scopes
    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha_confirmacion', today());
    }

    public function scopeConDiferencias($query)
    {
        return $query->where('estado', 'con_diferencia');
    }

    public function scopePendientesAjuste($query)
    {
        return $query->where('estado', 'pendiente_ajuste');
    }

    // Métodos
    public static function confirmarSaldo(CuentaBancaria $cuenta, float $saldoReal, ?string $notas = null): self
    {
        $saldoSistema = $cuenta->saldo_actual;
        $diferencia = $saldoReal - $saldoSistema;

        $estado = abs($diferencia) < 0.01 ? 'confirmado' : 'con_diferencia';

        return self::create([
            'cuenta_bancaria_id' => $cuenta->id,
            'saldo_sistema' => $saldoSistema,
            'saldo_real' => $saldoReal,
            'diferencia' => $diferencia,
            'estado' => $estado,
            'notas' => $notas,
            'fecha_confirmacion' => today(),
        ]);
    }

    public static function yaConfirmadoHoy(int $cuentaId): bool
    {
        return self::where('cuenta_bancaria_id', $cuentaId)
            ->whereDate('fecha_confirmacion', today())
            ->exists();
    }

    public static function todasConfirmadasHoy(): bool
    {
        $cuentasActivas = CuentaBancaria::activas()->count();
        $confirmacionesHoy = self::whereDate('fecha_confirmacion', today())
            ->distinct('cuenta_bancaria_id')
            ->count();

        return $confirmacionesHoy >= $cuentasActivas;
    }

    public static function fueOmitidoHoy(): bool
    {
        return OmisionConfirmacion::where('fecha', today())->exists();
    }

    // Accessors
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'confirmado' => 'Confirmado',
            'con_diferencia' => 'Con diferencia',
            'pendiente_ajuste' => 'Pendiente de ajuste',
            'ajustado' => 'Ajustado',
            default => $this->estado,
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'confirmado' => 'green',
            'con_diferencia' => 'yellow',
            'pendiente_ajuste' => 'red',
            'ajustado' => 'blue',
            default => 'gray',
        };
    }
}
