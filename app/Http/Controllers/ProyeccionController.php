<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Services\FlujoEfectivoService;
use Illuminate\Http\Request;

class ProyeccionController extends Controller
{
    protected FlujoEfectivoService $flujoService;

    public function __construct(FlujoEfectivoService $flujoService)
    {
        $this->flujoService = $flujoService;
    }

    /**
     * Proyección principal de flujo de efectivo
     */
    public function index(Request $request)
    {
        $dias = $request->input('dias', 30);
        $cuentaId = $request->input('cuenta_id');

        // Validar días
        $dias = in_array($dias, [30, 60, 90]) ? $dias : 30;

        // Generar proyección
        $proyeccion = $this->flujoService->generarProyeccion($dias, $cuentaId);

        // Datos para filtros
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();

        return view('proyeccion.index', compact('proyeccion', 'cuentas', 'dias', 'cuentaId'));
    }

    /**
     * Análisis de escenarios (optimista, pesimista, realista)
     */
    public function escenarios(Request $request)
    {
        $meses = $request->input('meses', 12);
        $meses = min(max($meses, 3), 24); // Entre 3 y 24 meses

        $escenarios = $this->flujoService->generarEscenarios($meses);

        return view('proyeccion.escenarios', compact('escenarios', 'meses'));
    }

    /**
     * Análisis de tendencias históricas
     */
    public function tendencias(Request $request)
    {
        $meses = $request->input('meses', 12);
        $meses = min(max($meses, 3), 24);

        $tendencias = $this->flujoService->analizarTendencias($meses);

        // Resumen por categorías
        $resumenCategorias = $this->flujoService->getResumenCategorias();

        // Salud financiera
        $saludFinanciera = $this->flujoService->calcularSaludFinanciera();

        return view('proyeccion.tendencias', compact('tendencias', 'resumenCategorias', 'saludFinanciera', 'meses'));
    }

    /**
     * Comparativo mensual (Real vs Proyectado)
     */
    public function comparativo(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $comparativo = $this->flujoService->getComparativoMes($mes, $anio);

        // Lista de meses disponibles (últimos 12 meses)
        $mesesDisponibles = [];
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $mesesDisponibles[] = [
                'mes' => $fecha->month,
                'anio' => $fecha->year,
                'label' => $fecha->format('F Y'),
            ];
        }

        return view('proyeccion.comparativo', compact('comparativo', 'mesesDisponibles', 'mes', 'anio'));
    }
}
