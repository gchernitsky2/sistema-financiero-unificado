<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\Movimiento;
use App\Models\PagoProgramado;
use App\Models\Prestamo;
use App\Models\MetaFinanciera;
use App\Models\Recordatorio;
use App\Services\FlujoEfectivoService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected FlujoEfectivoService $flujoService;

    public function __construct(FlujoEfectivoService $flujoService)
    {
        $this->flujoService = $flujoService;
    }

    public function index()
    {
        // Resumen del mes actual
        $resumenMes = $this->flujoService->getResumenMesActual();

        // Saldo por cuenta
        $saldosPorCuenta = $this->flujoService->getSaldoPorCuenta();

        // Pagos urgentes (próximos 7 días)
        $pagosUrgentes = $this->flujoService->getPagosUrgentes(7);

        // Próximos pagos (próximos 15 días)
        $proximosPagos = $this->flujoService->getProximosPagos(15);

        // Datos para gráfica combinada (real vs proyectado)
        $datosGrafica = $this->flujoService->getDatosGraficaCombinada(15);

        // Últimos movimientos
        $ultimosMovimientos = Movimiento::with(['cuentaBancaria', 'categoria'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Recordatorios pendientes
        $recordatorios = Recordatorio::pendientes()
            ->orderBy('fecha_recordatorio')
            ->limit(5)
            ->get();

        // Estadísticas adicionales
        $estadisticas = [
            'total_cuentas' => CuentaBancaria::activas()->count(),
            'pagos_vencidos' => PagoProgramado::vencidos()->count(),
            'prestamos_activos' => Prestamo::activos()->count(),
            'metas_activas' => MetaFinanciera::activas()->count(),
        ];

        // Indicadores de salud financiera
        $saludFinanciera = $this->flujoService->calcularSaludFinanciera();

        return view('dashboard.index', compact(
            'resumenMes',
            'saldosPorCuenta',
            'pagosUrgentes',
            'proximosPagos',
            'datosGrafica',
            'ultimosMovimientos',
            'recordatorios',
            'estadisticas',
            'saludFinanciera'
        ));
    }
}
