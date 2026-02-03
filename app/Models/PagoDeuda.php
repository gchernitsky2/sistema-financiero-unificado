<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDeuda extends Model
{
    use HasFactory;

    protected $table = 'pagos_deuda';

    protected $fillable = [
        'deuda_id',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    // Relaciones
    public function deuda(): BelongsTo
    {
        return $this->belongsTo(Deuda::class);
    }

    // Accessors
    public function getMetodoPagoLabelAttribute(): string
    {
        return match ($this->metodo_pago) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'tarjeta' => 'Tarjeta',
            'otro' => 'Otro',
            default => $this->metodo_pago ?? 'No especificado',
        };
    }
}
