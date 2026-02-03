<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\PagoPrestamo;
use App\Models\CuentaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $query = Prestamo::with('cuentaBancaria');

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('beneficiario')) {
            $query->where('beneficiario', 'like', "%{$request->beneficiario}%");
        }

        $prestamos = $query->orderByDesc('created_at')->paginate(20);

        // Resumen
        $resumen = [
            'activos' => Prestamo::activos()->count(),
            'en_mora' => Prestamo::enMora()->count(),
            'total_otorgado' => Prestamo::otorgados()->activos()->sum('saldo_pendiente'),
            'total_recibido' => Prestamo::recibidos()->activos()->sum('saldo_pendiente'),
        ];

        return view('prestamos.index', compact('prestamos', 'resumen'));
    }

    public function create()
    {
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();

        return view('prestamos.create', compact('cuentas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'tipo' => 'required|in:otorgado,recibido',
            'beneficiario' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'monto_principal' => 'required|numeric|min:0.01',
            'tasa_interes' => 'required|numeric|min:0|max:100',
            'tipo_interes' => 'required|in:simple,compuesto',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'frecuencia_pago' => 'required|in:unico,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
            'numero_pagos' => 'required|integer|min:1|max:360',
            'referencia' => 'nullable|string|max:100',
            'numero_contrato' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['estado'] = 'activo';
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $prestamo = Prestamo::create($validated);

            // Generar tabla de amortización
            $prestamo->generarTablaAmortizacion();
        });

        return redirect()->route('prestamos.index')
            ->with('success', 'Préstamo creado exitosamente con tabla de amortización.');
    }

    public function show(Prestamo $prestamo)
    {
        $prestamo->load(['cuentaBancaria', 'pagos' => function ($query) {
            $query->orderBy('numero_cuota');
        }]);

        return view('prestamos.show', compact('prestamo'));
    }

    public function edit(Prestamo $prestamo)
    {
        if ($prestamo->estado !== 'activo') {
            return back()->with('error', 'Solo se pueden editar préstamos activos.');
        }

        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();

        return view('prestamos.edit', compact('prestamo', 'cuentas'));
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        if ($prestamo->estado !== 'activo') {
            return back()->with('error', 'Solo se pueden editar préstamos activos.');
        }

        $validated = $request->validate([
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'beneficiario' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'referencia' => 'nullable|string|max:100',
            'numero_contrato' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
        ]);

        $prestamo->update($validated);

        return redirect()->route('prestamos.show', $prestamo)
            ->with('success', 'Préstamo actualizado exitosamente.');
    }

    public function destroy(Prestamo $prestamo)
    {
        if ($prestamo->pagos()->where('estado', 'pagado')->exists()) {
            return back()->with('error', 'No se puede eliminar un préstamo con pagos realizados.');
        }

        $prestamo->delete();

        return redirect()->route('prestamos.index')
            ->with('success', 'Préstamo eliminado exitosamente.');
    }

    /**
     * Registrar pago de cuota
     */
    public function registrarPago(Request $request, Prestamo $prestamo, PagoPrestamo $pago)
    {
        if ($pago->prestamo_id !== $prestamo->id) {
            abort(404);
        }

        if ($pago->estado === 'pagado') {
            return back()->with('error', 'Esta cuota ya fue pagada.');
        }

        $validated = $request->validate([
            'monto_pagado' => 'nullable|numeric|min:0.01',
            'comprobante' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($pago, $validated) {
            $pago->registrarPago(
                $validated['monto_pagado'] ?? null,
                $validated['comprobante'] ?? null
            );
        });

        return back()->with('success', 'Pago registrado exitosamente.');
    }

    /**
     * Ver tabla de amortización completa
     */
    public function amortizacion(Prestamo $prestamo)
    {
        $prestamo->load(['pagos' => function ($query) {
            $query->orderBy('numero_cuota');
        }]);

        return view('prestamos.amortizacion', compact('prestamo'));
    }

    /**
     * Regenerar tabla de amortización
     */
    public function regenerarAmortizacion(Prestamo $prestamo)
    {
        if ($prestamo->pagos()->where('estado', 'pagado')->exists()) {
            return back()->with('error', 'No se puede regenerar la tabla porque ya hay pagos realizados.');
        }

        $prestamo->generarTablaAmortizacion();

        return back()->with('success', 'Tabla de amortización regenerada exitosamente.');
    }

    /**
     * Liquidar préstamo (pagar saldo pendiente)
     */
    public function liquidar(Prestamo $prestamo)
    {
        if ($prestamo->estado !== 'activo') {
            return back()->with('error', 'Solo se pueden liquidar préstamos activos.');
        }

        $prestamo->liquidar();

        return back()->with('success', 'Préstamo liquidado exitosamente.');
    }

    /**
     * Cancelar préstamo
     */
    public function cancelar(Prestamo $prestamo)
    {
        $prestamo->cancelar();

        return back()->with('success', 'Préstamo cancelado exitosamente.');
    }
}
