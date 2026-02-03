<?php

namespace App\Http\Controllers;

use App\Models\MetaFinanciera;
use App\Models\CuentaBancaria;
use App\Models\Prestamo;
use Illuminate\Http\Request;

class MetaFinancieraController extends Controller
{
    public function index(Request $request)
    {
        $query = MetaFinanciera::with(['cuentaBancaria', 'prestamo']);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $metas = $query->ordenPrioridad()->paginate(20);

        // Resumen
        $resumen = [
            'activas' => MetaFinanciera::activas()->count(),
            'completadas' => MetaFinanciera::completadas()->count(),
            'total_objetivo' => MetaFinanciera::activas()->sum('monto_objetivo'),
            'total_actual' => MetaFinanciera::activas()->sum('monto_actual'),
            'en_riesgo' => MetaFinanciera::activas()->get()->filter(fn($m) => $m->esta_en_riesgo)->count(),
        ];

        return view('metas.index', compact('metas', 'resumen'));
    }

    public function create()
    {
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $prestamos = Prestamo::activos()->orderBy('beneficiario')->get();

        return view('metas.create', compact('cuentas', 'prestamos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:ahorro,pago_deuda,inversion,fondo_emergencia,compra,otro',
            'monto_objetivo' => 'required|numeric|min:0.01',
            'monto_actual' => 'nullable|numeric|min:0',
            'aporte_mensual' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_objetivo' => 'required|date|after:fecha_inicio',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'prestamo_id' => 'nullable|exists:prestamos,id',
            'prioridad' => 'required|integer|min:1|max:5',
            'color' => 'nullable|string|max:7',
            'icono' => 'nullable|string|max:50',
            'notificar_progreso' => 'boolean',
        ]);

        $validated['monto_actual'] = $validated['monto_actual'] ?? 0;
        $validated['estado'] = 'activa';
        $validated['notificar_progreso'] = $request->boolean('notificar_progreso', true);
        $validated['user_id'] = auth()->id();

        MetaFinanciera::create($validated);

        return redirect()->route('metas.index')
            ->with('success', 'Meta financiera creada exitosamente.');
    }

    public function show(MetaFinanciera $meta)
    {
        $meta->load(['cuentaBancaria', 'prestamo', 'aportes' => function ($query) {
            $query->orderByDesc('fecha')->limit(20);
        }]);

        // Historial de aportes por mes
        $historialAportes = $meta->getHistorialAportesPorMes(12);

        // Proyección
        $proyeccion = $meta->getProyeccion(12);

        return view('metas.show', compact('meta', 'historialAportes', 'proyeccion'));
    }

    public function edit(MetaFinanciera $meta)
    {
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $prestamos = Prestamo::activos()->orderBy('beneficiario')->get();

        return view('metas.edit', compact('meta', 'cuentas', 'prestamos'));
    }

    public function update(Request $request, MetaFinanciera $meta)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:ahorro,pago_deuda,inversion,fondo_emergencia,compra,otro',
            'monto_objetivo' => 'required|numeric|min:0.01',
            'aporte_mensual' => 'nullable|numeric|min:0',
            'fecha_objetivo' => 'required|date|after:fecha_inicio',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'prestamo_id' => 'nullable|exists:prestamos,id',
            'prioridad' => 'required|integer|min:1|max:5',
            'color' => 'nullable|string|max:7',
            'icono' => 'nullable|string|max:50',
            'notificar_progreso' => 'boolean',
        ]);

        $validated['notificar_progreso'] = $request->boolean('notificar_progreso', true);

        $meta->update($validated);

        return redirect()->route('metas.show', $meta)
            ->with('success', 'Meta financiera actualizada exitosamente.');
    }

    public function destroy(MetaFinanciera $meta)
    {
        $meta->delete();

        return redirect()->route('metas.index')
            ->with('success', 'Meta financiera eliminada exitosamente.');
    }

    /**
     * Registrar aporte a la meta
     */
    public function registrarAporte(Request $request, MetaFinanciera $meta)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'notas' => 'nullable|string|max:500',
        ]);

        $meta->registrarAporte($validated['monto'], $validated['notas'] ?? null);

        return back()->with('success', 'Aporte registrado exitosamente.');
    }

    /**
     * Cambiar estado de la meta
     */
    public function cambiarEstado(Request $request, MetaFinanciera $meta)
    {
        $validated = $request->validate([
            'estado' => 'required|in:activa,pausada,completada,cancelada',
        ]);

        $meta->cambiarEstado($validated['estado']);

        return back()->with('success', 'Estado de la meta actualizado.');
    }
}
