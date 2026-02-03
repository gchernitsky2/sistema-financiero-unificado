<?php

namespace App\Services;

use App\Models\CuentaBancaria;
use App\Models\Movimiento;
use App\Models\PagoProgramado;
use App\Models\FlujoProyectado;
use App\Models\Prestamo;
use App\Models\PagoPrestamo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FlujoEfectivoService
{
    /**
     * Obtener saldo actual total de todas las cuentas activas
     */
    public function getSaldoActual(): float
    {
        return CuentaBancaria::activas()->sum('saldo_actual');
    }

    /**
     * Obtener saldo por cuenta
     */
    public function getSaldoPorCuenta(): Collection
    {
        return CuentaBancaria::activas()
            ->with('banco')
            ->get()
            ->map(fn($cuenta) => [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre_completo,
                'tipo' => $cuenta->tipo_label,
                'saldo' => $cuenta->saldo_actual,
                'color' => $cuenta->color,
            ]);
    }

    /**
     * Obtener total de ingresos en un período
     */
    public function getTotalIngresos(?Carbon $desde = null, ?Carbon $hasta = null): float
    {
        $query = Movimiento::ingresos()->where('estado', '!=', 'cancelado');

        if ($desde && $hasta) {
            $query->whereBetween('fecha', [$desde, $hasta]);
        }

        return $query->sum('monto');
    }

    /**
     * Obtener total de egresos en un período
     */
    public function getTotalEgresos(?Carbon $desde = null, ?Carbon $hasta = null): float
    {
        $query = Movimiento::egresos()->where('estado', '!=', 'cancelado');

        if ($desde && $hasta) {
            $query->whereBetween('fecha', [$desde, $hasta]);
        }

        return $query->sum('monto');
    }

    /**
     * Obtener flujo neto en un período
     */
    public function getFlujoNeto(?Carbon $desde = null, ?Carbon $hasta = null): float
    {
        return $this->getTotalIngresos($desde, $hasta) - $this->getTotalEgresos($desde, $hasta);
    }

    /**
     * Obtener resumen del mes actual
     */
    public function getResumenMesActual(): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        return [
            'saldo_actual' => $this->getSaldoActual(),
            'ingresos' => $this->getTotalIngresos($inicioMes, $finMes),
            'egresos' => $this->getTotalEgresos($inicioMes, $finMes),
            'flujo_neto' => $this->getFlujoNeto($inicioMes, $finMes),
            'mes' => now()->format('F Y'),
        ];
    }

    /**
     * Obtener pagos urgentes (próximos 7 días)
     */
    public function getPagosUrgentes(int $dias = 7): Collection
    {
        return PagoProgramado::where('tipo', 'egreso')
            ->whereIn('estado', ['pendiente', 'programado', 'vencido'])
            ->where('fecha_programada', '<=', now()->addDays($dias))
            ->orderBy('fecha_programada')
            ->orderByDesc('prioridad_calculada')
            ->limit(10)
            ->get();
    }

    /**
     * Obtener próximos pagos programados
     */
    public function getProximosPagos(int $dias = 15): Collection
    {
        return PagoProgramado::whereIn('estado', ['pendiente', 'programado'])
            ->whereBetween('fecha_programada', [now(), now()->addDays($dias)])
            ->orderBy('fecha_programada')
            ->limit(10)
            ->get();
    }

    /**
     * Generar proyección de flujo de efectivo día a día
     */
    public function generarProyeccion(int $dias = 30, ?int $cuentaId = null): array
    {
        $saldoActual = $cuentaId
            ? CuentaBancaria::find($cuentaId)?->saldo_actual ?? 0
            : $this->getSaldoActual();

        $proyeccion = [];
        $saldoProyectado = $saldoActual;

        for ($i = 0; $i <= $dias; $i++) {
            $fecha = now()->addDays($i)->format('Y-m-d');
            $fechaCarbon = Carbon::parse($fecha);

            // Obtener flujos proyectados del día
            $flujosQuery = FlujoProyectado::whereDate('fecha_proyectada', $fecha)
                ->whereIn('estado', ['pendiente', 'parcial']);

            if ($cuentaId) {
                $flujosQuery->where('cuenta_bancaria_id', $cuentaId);
            }

            $flujos = $flujosQuery->get();

            // Obtener pagos programados del día
            $pagosQuery = PagoProgramado::whereDate('fecha_programada', $fecha)
                ->whereIn('estado', ['pendiente', 'programado']);

            if ($cuentaId) {
                $pagosQuery->where('cuenta_bancaria_id', $cuentaId);
            }

            $pagos = $pagosQuery->get();

            // Obtener cuotas de préstamos del día
            $cuotasQuery = PagoPrestamo::whereDate('fecha_programada', $fecha)
                ->where('estado', 'pendiente');

            $cuotas = $cuotasQuery->get();

            // Calcular totales del día
            $ingresosProyectados = $flujos->where('tipo', 'ingreso')->sum('monto')
                                 + $pagos->where('tipo', 'ingreso')->sum('monto');

            $egresosProyectados = $flujos->where('tipo', 'egreso')->sum('monto')
                                 + $pagos->where('tipo', 'egreso')->sum('monto')
                                 + $cuotas->sum('monto_total');

            $flujoNeto = $ingresosProyectados - $egresosProyectados;
            $saldoProyectado += $flujoNeto;

            // Verificar si hay pagos urgentes
            $tieneUrgentes = $pagos->where('es_urgente', true)->isNotEmpty() ||
                            $pagos->where('categoria_urgencia', 'critico')->isNotEmpty();

            $proyeccion[] = [
                'fecha' => $fecha,
                'fecha_formato' => $fechaCarbon->format('d/m'),
                'dia_semana' => $fechaCarbon->locale('es')->dayName,
                'ingresos' => $ingresosProyectados,
                'egresos' => $egresosProyectados,
                'flujo_neto' => $flujoNeto,
                'saldo_proyectado' => $saldoProyectado,
                'tiene_urgentes' => $tieneUrgentes,
                'es_dia_critico' => $saldoProyectado < 0,
                'detalles_flujos' => $flujos->count(),
                'detalles_pagos' => $pagos->count(),
                'detalles_cuotas' => $cuotas->count(),
            ];
        }

        return [
            'saldo_inicial' => $saldoActual,
            'saldo_final' => $saldoProyectado,
            'total_ingresos' => collect($proyeccion)->sum('ingresos'),
            'total_egresos' => collect($proyeccion)->sum('egresos'),
            'dias_criticos' => collect($proyeccion)->where('es_dia_critico', true)->count(),
            'proyeccion' => $proyeccion,
        ];
    }

    /**
     * Generar escenarios (optimista, pesimista, realista)
     */
    public function generarEscenarios(int $meses = 12): array
    {
        $saldoActual = $this->getSaldoActual();

        // Calcular promedios históricos (últimos 6 meses)
        $promedioIngresos = Movimiento::ingresos()
            ->where('fecha', '>=', now()->subMonths(6))
            ->avg('monto') * 30 ?? 0; // Promedio mensual

        $promedioEgresos = Movimiento::egresos()
            ->where('fecha', '>=', now()->subMonths(6))
            ->avg('monto') * 30 ?? 0;

        $escenarios = [
            'optimista' => [],
            'realista' => [],
            'pesimista' => [],
        ];

        $factores = [
            'optimista' => ['ingresos' => 1.2, 'egresos' => 0.9],
            'realista' => ['ingresos' => 1.0, 'egresos' => 1.0],
            'pesimista' => ['ingresos' => 0.8, 'egresos' => 1.15],
        ];

        foreach ($factores as $escenario => $factor) {
            $saldo = $saldoActual;

            for ($i = 1; $i <= $meses; $i++) {
                $ingresos = $promedioIngresos * $factor['ingresos'];
                $egresos = $promedioEgresos * $factor['egresos'];
                $saldo += $ingresos - $egresos;

                $escenarios[$escenario][] = [
                    'mes' => now()->addMonths($i)->format('M Y'),
                    'ingresos' => round($ingresos, 2),
                    'egresos' => round($egresos, 2),
                    'saldo' => round($saldo, 2),
                ];
            }
        }

        return $escenarios;
    }

    /**
     * Analizar tendencias históricas
     */
    public function analizarTendencias(int $meses = 12): array
    {
        $tendencias = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $inicioMes = $fecha->copy()->startOfMonth();
            $finMes = $fecha->copy()->endOfMonth();

            $ingresos = $this->getTotalIngresos($inicioMes, $finMes);
            $egresos = $this->getTotalEgresos($inicioMes, $finMes);

            $tendencias[] = [
                'mes' => $fecha->format('M Y'),
                'anio' => $fecha->year,
                'mes_num' => $fecha->month,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'flujo_neto' => $ingresos - $egresos,
            ];
        }

        // Calcular indicadores
        $promedioIngresos = collect($tendencias)->avg('ingresos');
        $promedioEgresos = collect($tendencias)->avg('egresos');
        $promedioFlujo = collect($tendencias)->avg('flujo_neto');

        // Determinar tendencia (subiendo, bajando, estable)
        $ultimosTres = collect($tendencias)->take(-3);
        $primerosTres = collect($tendencias)->take(3);

        $tendenciaIngresos = $ultimosTres->avg('ingresos') - $primerosTres->avg('ingresos');
        $tendenciaEgresos = $ultimosTres->avg('egresos') - $primerosTres->avg('egresos');

        return [
            'historico' => $tendencias,
            'promedios' => [
                'ingresos' => round($promedioIngresos, 2),
                'egresos' => round($promedioEgresos, 2),
                'flujo_neto' => round($promedioFlujo, 2),
            ],
            'tendencia' => [
                'ingresos' => $tendenciaIngresos > 0 ? 'subiendo' : ($tendenciaIngresos < 0 ? 'bajando' : 'estable'),
                'egresos' => $tendenciaEgresos > 0 ? 'subiendo' : ($tendenciaEgresos < 0 ? 'bajando' : 'estable'),
            ],
        ];
    }

    /**
     * Calcular indicadores de salud financiera
     */
    public function calcularSaludFinanciera(): array
    {
        $saldoActual = $this->getSaldoActual();
        $promedioEgresosMensual = Movimiento::egresos()
            ->where('fecha', '>=', now()->subMonths(3))
            ->avg('monto') * 30 ?? 1;

        // Ratio de liquidez (meses de cobertura)
        $mesesCobertura = $promedioEgresosMensual > 0
            ? $saldoActual / $promedioEgresosMensual
            : 0;

        // Volatilidad del flujo (desviación estándar)
        $flujosMensuales = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $inicioMes = $fecha->copy()->startOfMonth();
            $finMes = $fecha->copy()->endOfMonth();
            $flujosMensuales[] = $this->getFlujoNeto($inicioMes, $finMes);
        }

        $promedio = collect($flujosMensuales)->avg();
        $varianza = collect($flujosMensuales)->map(fn($f) => pow($f - $promedio, 2))->avg();
        $desviacion = sqrt($varianza);
        $coeficienteVariacion = $promedio != 0 ? ($desviacion / abs($promedio)) * 100 : 0;

        // Determinar estado de salud
        $estadoSalud = 'buena';
        if ($mesesCobertura < 1 || $coeficienteVariacion > 50) {
            $estadoSalud = 'critica';
        } elseif ($mesesCobertura < 3 || $coeficienteVariacion > 30) {
            $estadoSalud = 'regular';
        }

        return [
            'saldo_actual' => round($saldoActual, 2),
            'promedio_egresos_mensual' => round($promedioEgresosMensual, 2),
            'meses_cobertura' => round($mesesCobertura, 1),
            'volatilidad' => round($coeficienteVariacion, 1),
            'estado' => $estadoSalud,
            'recomendaciones' => $this->generarRecomendaciones($mesesCobertura, $coeficienteVariacion),
        ];
    }

    /**
     * Generar recomendaciones basadas en la salud financiera
     */
    protected function generarRecomendaciones(float $mesesCobertura, float $volatilidad): array
    {
        $recomendaciones = [];

        if ($mesesCobertura < 1) {
            $recomendaciones[] = 'Urgente: Tu saldo actual no cubre ni un mes de gastos. Considera reducir gastos o aumentar ingresos.';
        } elseif ($mesesCobertura < 3) {
            $recomendaciones[] = 'Se recomienda tener al menos 3 meses de gastos como fondo de emergencia.';
        }

        if ($volatilidad > 50) {
            $recomendaciones[] = 'Tu flujo de efectivo es muy variable. Considera estabilizar tus fuentes de ingreso.';
        } elseif ($volatilidad > 30) {
            $recomendaciones[] = 'Hay cierta variabilidad en tu flujo. Planifica mejor los gastos variables.';
        }

        // Verificar pagos vencidos
        $pagosVencidos = PagoProgramado::vencidos()->count();
        if ($pagosVencidos > 0) {
            $recomendaciones[] = "Tienes {$pagosVencidos} pago(s) vencido(s). Prioriza su liquidación.";
        }

        // Verificar préstamos en mora
        $prestamosEnMora = Prestamo::enMora()->count();
        if ($prestamosEnMora > 0) {
            $recomendaciones[] = "Tienes {$prestamosEnMora} préstamo(s) en mora. Contacta a tus acreedores.";
        }

        if (empty($recomendaciones)) {
            $recomendaciones[] = '¡Tu salud financiera es buena! Sigue manteniendo el control de tus finanzas.';
        }

        return $recomendaciones;
    }

    /**
     * Obtener resumen por categorías
     */
    public function getResumenCategorias(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $desde = $desde ?? now()->startOfMonth();
        $hasta = $hasta ?? now()->endOfMonth();

        $movimientos = Movimiento::with('categoria')
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('estado', '!=', 'cancelado')
            ->get();

        $porCategoria = $movimientos->groupBy('categoria_id')->map(function ($items, $categoriaId) {
            $categoria = $items->first()->categoria;
            return [
                'categoria_id' => $categoriaId,
                'categoria' => $categoria?->nombre ?? 'Sin categoría',
                'color' => $categoria?->color ?? '#6B7280',
                'ingresos' => $items->where('tipo', 'ingreso')->sum('monto'),
                'egresos' => $items->where('tipo', 'egreso')->sum('monto'),
                'total' => $items->where('tipo', 'ingreso')->sum('monto') - $items->where('tipo', 'egreso')->sum('monto'),
                'transacciones' => $items->count(),
            ];
        })->values();

        return [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'categorias' => $porCategoria,
        ];
    }

    /**
     * Obtener datos para gráfica combinada (real vs proyectado)
     */
    public function getDatosGraficaCombinada(int $dias = 15): array
    {
        $datos = [];

        // Datos históricos (últimos días)
        for ($i = $dias; $i > 0; $i--) {
            $fecha = now()->subDays($i);
            $ingresos = Movimiento::ingresos()
                ->whereDate('fecha', $fecha)
                ->where('estado', '!=', 'cancelado')
                ->sum('monto');
            $egresos = Movimiento::egresos()
                ->whereDate('fecha', $fecha)
                ->where('estado', '!=', 'cancelado')
                ->sum('monto');

            $datos[] = [
                'fecha' => $fecha->format('d/m'),
                'ingresos_real' => $ingresos,
                'egresos_real' => $egresos,
                'ingresos_proyectado' => null,
                'egresos_proyectado' => null,
            ];
        }

        // Datos proyectados (próximos días)
        for ($i = 0; $i <= $dias; $i++) {
            $fecha = now()->addDays($i);

            $ingresosProyectados = FlujoProyectado::ingresos()
                ->whereDate('fecha_proyectada', $fecha)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->sum('monto');

            $egresosProyectados = FlujoProyectado::egresos()
                ->whereDate('fecha_proyectada', $fecha)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->sum('monto');

            $egresosPagos = PagoProgramado::where('tipo', 'egreso')
                ->whereDate('fecha_programada', $fecha)
                ->whereIn('estado', ['pendiente', 'programado'])
                ->sum('monto');

            $datos[] = [
                'fecha' => $fecha->format('d/m'),
                'ingresos_real' => null,
                'egresos_real' => null,
                'ingresos_proyectado' => $ingresosProyectados,
                'egresos_proyectado' => $egresosProyectados + $egresosPagos,
            ];
        }

        return $datos;
    }

    /**
     * Comparativo: Real vs Proyectado del mes
     */
    public function getComparativoMes(int $mes = null, int $anio = null): array
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth();

        // Movimientos reales
        $ingresosReales = Movimiento::ingresos()
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('estado', '!=', 'cancelado')
            ->sum('monto');

        $egresosReales = Movimiento::egresos()
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('estado', '!=', 'cancelado')
            ->sum('monto');

        // Flujos proyectados
        $ingresosProyectados = FlujoProyectado::ingresos()
            ->whereBetween('fecha_proyectada', [$inicioMes, $finMes])
            ->sum('monto');

        $egresosProyectados = FlujoProyectado::egresos()
            ->whereBetween('fecha_proyectada', [$inicioMes, $finMes])
            ->sum('monto');

        return [
            'periodo' => $inicioMes->format('F Y'),
            'ingresos' => [
                'real' => $ingresosReales,
                'proyectado' => $ingresosProyectados,
                'variacion' => $ingresosReales - $ingresosProyectados,
                'porcentaje' => $ingresosProyectados > 0
                    ? round((($ingresosReales - $ingresosProyectados) / $ingresosProyectados) * 100, 1)
                    : 0,
            ],
            'egresos' => [
                'real' => $egresosReales,
                'proyectado' => $egresosProyectados,
                'variacion' => $egresosReales - $egresosProyectados,
                'porcentaje' => $egresosProyectados > 0
                    ? round((($egresosReales - $egresosProyectados) / $egresosProyectados) * 100, 1)
                    : 0,
            ],
            'flujo_neto' => [
                'real' => $ingresosReales - $egresosReales,
                'proyectado' => $ingresosProyectados - $egresosProyectados,
            ],
        ];
    }
}
