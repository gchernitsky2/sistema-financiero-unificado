<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmisionConfirmacion extends Model
{
    use HasFactory;

    protected $table = 'omisiones_confirmacion';

    protected $fillable = [
        'fecha',
        'motivo',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public static function omitirHoy(?string $motivo = null): self
    {
        return self::updateOrCreate(
            ['fecha' => today()],
            ['motivo' => $motivo]
        );
    }

    public static function fueOmitido(\DateTimeInterface $fecha): bool
    {
        return self::whereDate('fecha', $fecha)->exists();
    }
}
