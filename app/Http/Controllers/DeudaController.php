<?php

namespace App\Http\Controllers;

use App\Models\Deuda;
use App\Models\PagoDeuda;
use App\Models\CuentaBancaria;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeudaController extends Controller
{
    /**
     * Listado de deudas con filtros
     */
    public function index(Request $request)
    {
        $tipo = $request->get('type', 'all');
        $estado = $request->get('status', 'all');
        $busqueda = $request->get('search');

        $query = Deuda::with(['cuentaBancaria', 'categoria'])
            ->filtrarPorTipo($tipo)
            ->filtrarPorEstado($estado);

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('persona_nombre', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        $deudas = $query->orderByRaw("CASE
            WHEN estado = 'vencido' THEN 1
            WHEN estado = 'parcial' THEN 2
            WHEN estado = 'pendiente' THEN 3
            ELSE 4 END")
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('monto_original')
            ->paginate(20)
            ->withQueryString();

        // Estadísticas
        $estadisticas = [
            'por_cobrar' => Deuda::getEstadisticas('receivable'),
            'por_pagar' => Deuda::getEstadisticas('payable'),
            'todas' => Deuda::getEstadisticas(),
        ];

        // Deudas urgentes (próximas a vencer)
        $urgentes = Deuda::with(['cuentaBancaria'])
            ->proximasVencer(7)
            ->orderBy('fecha_vencimiento')
            ->limit(5)
            ->get();

        // Cuentas para el formulario
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();

        return view('deudas.index', compact(
            'deudas',
            'tipo',
            'estado',
            'busqueda',
            'estadisticas',
            'urgentes',
            'cuentas',
            'categorias'
        ));
    }

    /**
     * Formulario para crear nueva deuda
     */
    public function create(Request $request)
    {
        $tipo = $request->get('type', 'payable');
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();

        return view('deudas.create', compact('tipo', 'cuentas', 'categorias'));
    }

    /**
     * Guardar nueva deuda
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:receivable,payable',
            'persona_nombre' => 'required|string|max:255',
            'persona_telefono' => 'nullable|string|max:50',
            'persona_email' => 'nullable|email|max:255',
            'descripcion' => 'required|string|max:1000',
            'monto_original' => 'required|numeric|min:0.01',
            'fecha_creacion' => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_creacion',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'notas' => 'nullable|string|max:2000',
            'prioridad' => 'nullable|integer|min:-1|max:1',
        ]);

        $validated['estado'] = 'pendiente';

        $deuda = Deuda::create($validated);

        return redirect()->route('deudas.index', ['type' => $validated['tipo']])
            ->with('success', 'Deuda registrada exitosamente.');
    }

    /**
     * Ver detalle de deuda
     */
    public function show(Deuda $deuda)
    {
        $deuda->load(['cuentaBancaria', 'categoria', 'pagos' => function ($q) {
            $q->orderByDesc('fecha_pago');
        }]);

        return view('deudas.show', compact('deuda'));
    }

    /**
     * Formulario de edición
     */
    public function edit(Deuda $deuda)
    {
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();

        return view('deudas.edit', compact('deuda', 'cuentas', 'categorias'));
    }

    /**
     * Actualizar deuda
     */
    public function update(Request $request, Deuda $deuda)
    {
        $validated = $request->validate([
            'persona_nombre' => 'required|string|max:255',
            'persona_telefono' => 'nullable|string|max:50',
            'persona_email' => 'nullable|email|max:255',
            'descripcion' => 'required|string|max:1000',
            'monto_original' => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'nullable|date',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'notas' => 'nullable|string|max:2000',
            'prioridad' => 'nullable|integer|min:-1|max:1',
        ]);

        $deuda->update($validated);
        $deuda->actualizarEstado();
        $deuda->save();

        return redirect()->route('deudas.show', $deuda)
            ->with('success', 'Deuda actualizada exitosamente.');
    }

    /**
     * Eliminar deuda
     */
    public function destroy(Deuda $deuda)
    {
        $tipo = $deuda->tipo;
        $deuda->delete();

        return redirect()->route('deudas.index', ['type' => $tipo])
            ->with('success', 'Deuda eliminada exitosamente.');
    }

    /**
     * Registrar un pago
     */
    public function registrarPago(Request $request, Deuda $deuda)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . ($deuda->monto_original - $deuda->monto_pagado),
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $pago = $deuda->pagos()->create($validated);
            $deuda->monto_pagado += $validated['monto'];
            $deuda->actualizarEstado();
            $deuda->save();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pago registrado exitosamente.',
                    'deuda' => $deuda->fresh(),
                    'pago' => $pago,
                ]);
            }

            return redirect()->route('deudas.show', $deuda)
                ->with('success', 'Pago registrado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar pago: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al registrar pago: ' . $e->getMessage());
        }
    }

    /**
     * Marcar como pagada completamente
     */
    public function marcarPagada(Deuda $deuda)
    {
        $montoPendiente = $deuda->monto_original - $deuda->monto_pagado;

        if ($montoPendiente > 0) {
            $deuda->pagos()->create([
                'monto' => $montoPendiente,
                'fecha_pago' => now(),
                'metodo_pago' => 'otro',
                'notas' => 'Liquidación completa',
            ]);

            $deuda->monto_pagado = $deuda->monto_original;
        }

        $deuda->estado = 'pagado';
        $deuda->save();

        return redirect()->back()
            ->with('success', 'Deuda marcada como pagada.');
    }

    /**
     * Cancelar deuda
     */
    public function cancelar(Deuda $deuda)
    {
        $deuda->cancelar();

        return redirect()->back()
            ->with('success', 'Deuda cancelada.');
    }

    /**
     * API: Obtener estadísticas
     */
    public function estadisticas(Request $request)
    {
        $tipo = $request->get('type', 'all');

        return response()->json([
            'estadisticas' => Deuda::getEstadisticas($tipo),
        ]);
    }
}
