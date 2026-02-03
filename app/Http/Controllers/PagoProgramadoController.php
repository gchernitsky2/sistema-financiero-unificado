<?php

namespace App\Http\Controllers;

use App\Models\PagoProgramado;
use App\Models\CuentaBancaria;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoProgramadoController extends Controller
{
    public function index(Request $request)
    {
        $query = PagoProgramado::with(['cuentaBancaria', 'categoria']);

        // Filtros
        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_bancaria_id', $request->cuenta_id);
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_programada', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_programada', '<=', $request->fecha_hasta);
        }

        if ($request->boolean('solo_urgentes')) {
            $query->where(function ($q) {
                $q->where('es_urgente', true)
                  ->orWhere('categoria_urgencia', 'urgente')
                  ->orWhere('categoria_urgencia', 'critico');
            });
        }

        // Ordenar por prioridad calculada y fecha
        $query->ordenPrioridad();

        $pagos = $query->paginate(30)->withQueryString();

        // Actualizar vencidos y recalcular prioridades
        $this->actualizarEstados();

        // Datos para filtros
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        // Resumen
        $resumen = [
            'pendientes' => PagoProgramado::pendientes()->count(),
            'vencidos' => PagoProgramado::vencidos()->count(),
            'urgentes' => PagoProgramado::urgentes()->whereIn('estado', ['pendiente', 'programado'])->count(),
            'criticos' => PagoProgramado::criticos()->whereIn('estado', ['pendiente', 'programado'])->count(),
            'proximos_7_dias' => PagoProgramado::proximosVencer(7)->count(),
            'total_pendiente' => PagoProgramado::pendientes()->sum('monto'),
        ];

        return view('pagos-programados.index', compact('pagos', 'cuentas', 'categorias', 'resumen'));
    }

    public function create()
    {
        $cuentas = CuentaBancaria::activas()->with('banco')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('pagos-programados.create', compact('cuentas', 'categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'tipo' => 'required|in:ingreso,egreso',
            'beneficiario' => 'nullable|string|max:255',
            'concepto' => 'required|string|max:500',
            'descripcion' => 'nullable|string|max:1000',
            'monto' => 'required|numeric|min:0.01',
            'fecha_programada' => 'required|date',
            'recurrencia' => 'required|in:unico,diario,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
            'fecha_fin_recurrencia' => 'nullable|date|after:fecha_programada',
            'es_urgente' => 'boolean',
            'es_critico' => 'boolean',
            'prioridad_manual' => 'nullable|integer|min:1|max:10',
            'categoria_urgencia' => 'required|in:critico,urgente,normal,diferible,opcional',
            'tipo_pago' => 'required|in:fijo,variable,estimado',
            'tiene_mora' => 'boolean',
            'porcentaje_mora' => 'nullable|numeric|min:0|max:100',
            'numero_factura' => 'nullable|string|max:100',
            'numero_contrato' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['es_urgente'] = $request->boolean('es_urgente', false);
        $validated['es_critico'] = $request->boolean('es_critico', false);
        $validated['tiene_mora'] = $request->boolean('tiene_mora', false);
        $validated['es_recurrente'] = $validated['recurrencia'] !== 'unico';
        $validated['estado'] = 'pendiente';
        $validated['user_id'] = auth()->id();

        $pago = PagoProgramado::create($validated);

        // Calcular prioridad IA
        $pago->actualizarDiasParaVencer();
        $pago->calcularPrioridad();

        return redirect()->route('pagos-programados.index')
            ->with('success', 'Pago programado creado exitosamente.');
    }

    public function show(PagoProgramado $pagosProgramado)
    {
        $pagosProgramado->load(['cuentaBancaria.banco', 'categoria', 'movimiento']);

        return view('pagos-programados.show', compact('pagosProgramado'));
    }

    public function edit(PagoProgramado $pagosProgramado)
    {
        $cuentas = CuentaBancaria::activas()->with('banco')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('pagos-programados.edit', compact('pagosProgramado', 'cuentas', 'categorias'));
    }

    public function update(Request $request, PagoProgramado $pagosProgramado)
    {
        if ($pagosProgramado->estado === 'pagado') {
            return back()->with('error', 'No se puede editar un pago ya realizado.');
        }

        $validated = $request->validate([
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'tipo' => 'required|in:ingreso,egreso',
            'beneficiario' => 'nullable|string|max:255',
            'concepto' => 'required|string|max:500',
            'descripcion' => 'nullable|string|max:1000',
            'monto' => 'required|numeric|min:0.01',
            'fecha_programada' => 'required|date',
            'recurrencia' => 'required|in:unico,diario,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
            'fecha_fin_recurrencia' => 'nullable|date|after:fecha_programada',
            'es_urgente' => 'boolean',
            'es_critico' => 'boolean',
            'prioridad_manual' => 'nullable|integer|min:1|max:10',
            'categoria_urgencia' => 'required|in:critico,urgente,normal,diferible,opcional',
            'tipo_pago' => 'required|in:fijo,variable,estimado',
            'tiene_mora' => 'boolean',
            'porcentaje_mora' => 'nullable|numeric|min:0|max:100',
            'numero_factura' => 'nullable|string|max:100',
            'numero_contrato' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['es_urgente'] = $request->boolean('es_urgente', false);
        $validated['es_critico'] = $request->boolean('es_critico', false);
        $validated['tiene_mora'] = $request->boolean('tiene_mora', false);
        $validated['es_recurrente'] = $validated['recurrencia'] !== 'unico';

        $pagosProgramado->update($validated);

        // Recalcular prioridad
        $pagosProgramado->actualizarDiasParaVencer();
        $pagosProgramado->calcularPrioridad();

        return redirect()->route('pagos-programados.index')
            ->with('success', 'Pago programado actualizado exitosamente.');
    }

    public function destroy(PagoProgramado $pagosProgramado)
    {
        $pagosProgramado->delete();

        return redirect()->route('pagos-programados.index')
            ->with('success', 'Pago programado eliminado exitosamente.');
    }

    /**
     * Marcar pago como realizado
     */
    public function marcarPagado(Request $request, PagoProgramado $pagosProgramado)
    {
        $validated = $request->validate([
            'monto_pagado' => 'nullable|numeric|min:0.01',
        ]);

        $montoPagado = $validated['monto_pagado'] ?? null;

        DB::transaction(function () use ($pagosProgramado, $montoPagado) {
            $movimiento = $pagosProgramado->marcarPagado($montoPagado);
        });

        return back()->with('success', 'Pago registrado exitosamente.');
    }

    /**
     * Cancelar pago programado
     */
    public function cancelar(PagoProgramado $pagosProgramado)
    {
        $pagosProgramado->cancelar();

        return back()->with('success', 'Pago cancelado exitosamente.');
    }

    /**
     * Dashboard con IA - Priorización inteligente
     */
    public function dashboardIA()
    {
        // Recalcular todas las prioridades
        $this->recalcularPrioridades();

        // Obtener pagos ordenados por prioridad
        $pagosPriorizados = PagoProgramado::with(['cuentaBancaria', 'categoria'])
            ->whereIn('estado', ['pendiente', 'programado', 'vencido'])
            ->ordenPrioridad()
            ->limit(20)
            ->get();

        // Estadísticas
        $estadisticas = [
            'total_pendiente' => PagoProgramado::pendientes()->sum('monto'),
            'total_vencido' => PagoProgramado::vencidos()->sum('monto'),
            'total_con_mora' => PagoProgramado::where('tiene_mora', true)->sum('monto_mora'),
            'por_urgencia' => [
                'critico' => PagoProgramado::criticos()->whereIn('estado', ['pendiente', 'programado', 'vencido'])->sum('monto'),
                'urgente' => PagoProgramado::urgentes()->whereIn('estado', ['pendiente', 'programado', 'vencido'])->sum('monto'),
            ],
        ];

        // Recomendaciones basadas en IA
        $recomendaciones = $this->generarRecomendacionesIA();

        return view('pagos-programados.dashboard-ia', compact('pagosPriorizados', 'estadisticas', 'recomendaciones'));
    }

    /**
     * Recalcular prioridades de todos los pagos pendientes
     */
    public function recalcularPrioridades()
    {
        PagoProgramado::whereIn('estado', ['pendiente', 'programado', 'vencido'])
            ->get()
            ->each(function ($pago) {
                $pago->actualizarDiasParaVencer();
                $pago->calcularPrioridad();
                $pago->calcularMora();
            });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Prioridades recalculadas']);
        }

        return back()->with('success', 'Prioridades recalculadas exitosamente.');
    }

    /**
     * Actualizar estados (marcar vencidos)
     */
    protected function actualizarEstados()
    {
        PagoProgramado::where('estado', 'pendiente')
            ->where('fecha_programada', '<', now())
            ->update(['estado' => 'vencido']);
    }

    /**
     * Generar recomendaciones basadas en IA
     */
    protected function generarRecomendacionesIA(): array
    {
        $recomendaciones = [];

        // Verificar pagos críticos próximos
        $criticosProximos = PagoProgramado::criticos()
            ->proximosVencer(3)
            ->count();

        if ($criticosProximos > 0) {
            $recomendaciones[] = [
                'tipo' => 'alerta',
                'mensaje' => "Tienes {$criticosProximos} pago(s) crítico(s) en los próximos 3 días.",
                'accion' => 'Prioriza estos pagos inmediatamente.',
            ];
        }

        // Verificar pagos vencidos
        $vencidos = PagoProgramado::vencidos()->count();
        if ($vencidos > 0) {
            $recomendaciones[] = [
                'tipo' => 'urgente',
                'mensaje' => "Tienes {$vencidos} pago(s) vencido(s).",
                'accion' => 'Liquida estos pagos para evitar mora adicional.',
            ];
        }

        // Verificar acumulación de mora
        $totalMora = PagoProgramado::where('tiene_mora', true)->sum('monto_mora');
        if ($totalMora > 0) {
            $recomendaciones[] = [
                'tipo' => 'info',
                'mensaje' => "Tienes $" . number_format($totalMora, 2) . " acumulados en mora.",
                'accion' => 'Considera negociar con acreedores para reducir mora.',
            ];
        }

        // Sugerencia de optimización
        $pagosRecurrentes = PagoProgramado::where('es_recurrente', true)
            ->whereIn('estado', ['pendiente', 'programado'])
            ->count();

        if ($pagosRecurrentes > 5) {
            $recomendaciones[] = [
                'tipo' => 'sugerencia',
                'mensaje' => "Tienes {$pagosRecurrentes} pagos recurrentes programados.",
                'accion' => 'Considera domiciliar los pagos fijos para evitar olvidos.',
            ];
        }

        if (empty($recomendaciones)) {
            $recomendaciones[] = [
                'tipo' => 'success',
                'mensaje' => '¡Todo en orden!',
                'accion' => 'No tienes pagos urgentes ni vencidos.',
            ];
        }

        return $recomendaciones;
    }
}
