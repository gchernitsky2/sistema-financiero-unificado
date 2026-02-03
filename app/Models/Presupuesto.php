<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presupuesto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'anio',
        'mes',
        'categoria_id',
        'tipo',
        'monto_presupuestado',
        'monto_ejecutado',
        'variacion',
        'porcentaje_ejecucion',
        'activo',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'monto_presupuestado' => 'decimal:2',
        'monto_ejecutado' => 'decimal:2',
        'variacion' => 'decimal:2',
        'porcentaje_ejecucion' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelAnio($query, $anio = null)
    {
        return $query->where('anio', $anio ?? now()->year);
    }

    public function scopeDelMes($query, $mes = null, $anio = null)
    {
        return $query->where('anio', $anio ?? now()->year)
                     ->where('mes', $mes ?? now()->month);
    }

    public function scopeAnuales($query, $anio = null)
    {
        return $query->where('anio', $anio ?? now()->year)->where('mes', 0);
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    // Métodos
    public function actualizarEjecucion(): void
    {
        $query = Movimiento::where('tipo', $this->tipo);

        if ($this->categoria_id) {
            $query->where('categoria_id', $this->categoria_id);
        }

        if ($this->mes > 0) {
            $query->whereMonth('fecha', $this->mes)->whereYear('fecha', $this->anio);
        } else {
            $query->whereYear('fecha', $this->anio);
        }

        $this->monto_ejecutado = $query->sum('monto');
        $this->calcularVariacion();
        $this->save();
    }

    public function calcularVariacion(): void
    {
        $this->variacion = $this->monto_ejecutado - $this->monto_presupuestado;

        if ($this->monto_presupuestado > 0) {
            $this->porcentaje_ejecucion = ($this->monto_ejecutado / $this->monto_presupuestado) * 100;
        } else {
            $this->porcentaje_ejecucion = 0;
        }
    }

    public static function generarDesdeFlujos(int $anio, int $mes = 0): void
    {
        $query = FlujoProyectado::whereYear('fecha_proyectada', $anio);

        if ($mes > 0) {
            $query->whereMonth('fecha_proyectada', $mes);
        }

        $flujos = $query->selectRaw('categoria_id, tipo, SUM(monto) as total')
                        ->groupBy('categoria_id', 'tipo')
                        ->get();

        foreach ($flujos as $flujo) {
            static::updateOrCreate(
                [
                    'anio' => $anio,
                    'mes' => $mes,
                    'categoria_id' => $flujo->categoria_id,
                    'tipo' => $flujo->tipo,
                ],
                [
                    'nombre' => "Presupuesto {$flujo->tipo} " . ($mes > 0 ? "mes {$mes}" : 'anual') . " {$anio}",
                    'monto_presupuestado' => $flujo->total,
                    'activo' => true,
                ]
            );
        }
    }

    public static function duplicarAnio(int $anioOrigen, int $anioDestino): void
    {
        $presupuestos = static::where('anio', $anioOrigen)->get();

        foreach ($presupuestos as $presupuesto) {
            static::create([
                'nombre' => str_replace($anioOrigen, $anioDestino, $presupuesto->nombre),
                'descripcion' => $presupuesto->descripcion,
                'anio' => $anioDestino,
                'mes' => $presupuesto->mes,
                'categoria_id' => $presupuesto->categoria_id,
                'tipo' => $presupuesto->tipo,
                'monto_presupuestado' => $presupuesto->monto_presupuestado,
                'monto_ejecutado' => 0,
                'variacion' => 0,
                'porcentaje_ejecucion' => 0,
                'activo' => true,
                'user_id' => $presupuesto->user_id,
            ]);
        }
    }

    // Accessors
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'ingreso' ? 'Ingreso' : 'Egreso';
    }

    public function getPeriodoLabelAttribute(): string
    {
        if ($this->mes === 0) {
            return "Anual {$this->anio}";
        }

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return "{$meses[$this->mes]} {$this->anio}";
    }

    public function getEstadoCumplimientoAttribute(): string
    {
        if ($this->porcentaje_ejecucion >= 100) return 'excedido';
        if ($this->porcentaje_ejecucion >= 80) return 'alerta';
        if ($this->porcentaje_ejecucion >= 50) return 'normal';
        return 'bajo';
    }

    public function getColorCumplimientoAttribute(): string
    {
        // Para egresos: menos es mejor
        if ($this->tipo === 'egreso') {
            if ($this->porcentaje_ejecucion > 100) return 'red';
            if ($this->porcentaje_ejecucion > 80) return 'orange';
            return 'green';
        }

        // Para ingresos: más es mejor
        if ($this->porcentaje_ejecucion >= 100) return 'green';
        if ($this->porcentaje_ejecucion >= 80) return 'orange';
        return 'red';
    }

    public function getMontoRestanteAttribute(): float
    {
        return max(0, $this->monto_presupuestado - $this->monto_ejecutado);
    }
}
