<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\Banco;
use Illuminate\Http\Request;

class CuentaBancariaController extends Controller
{
    public function index(Request $request)
    {
        $query = CuentaBancaria::with('banco');

        // Filtros
        if ($request->filled('banco_id')) {
            $query->where('banco_id', $request->banco_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        $cuentas = $query->orderByDesc('es_principal')
            ->orderBy('nombre')
            ->paginate(20);

        $bancos = Banco::activos()->orderBy('nombre')->get();

        return view('cuentas.index', compact('cuentas', 'bancos'));
    }

    public function create()
    {
        $bancos = Banco::activos()->orderBy('nombre')->get();
        return view('cuentas.create', compact('bancos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'banco_id' => 'required|exists:bancos,id',
            'nombre' => 'required|string|max:255',
            'numero_cuenta' => 'nullable|string|max:50',
            'clabe' => 'nullable|string|max:20',
            'tipo' => 'required|in:banco,efectivo,tarjeta,inversion,otros',
            'moneda' => 'required|string|max:3',
            'saldo_inicial' => 'required|numeric|min:0',
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'descripcion' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['saldo_actual'] = $validated['saldo_inicial'];
        $validated['es_principal'] = $request->boolean('es_principal', false);
        $validated['activa'] = $request->boolean('activa', true);

        $cuenta = CuentaBancaria::create($validated);

        // Si es principal, quitar de otras
        if ($cuenta->es_principal) {
            CuentaBancaria::where('id', '!=', $cuenta->id)
                ->update(['es_principal' => false]);
        }

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta bancaria creada exitosamente.');
    }

    public function show(CuentaBancaria $cuenta)
    {
        $cuenta->load(['banco', 'movimientos' => function ($query) {
            $query->orderByDesc('fecha')->orderByDesc('id')->limit(20);
        }]);

        // Resumen de la cuenta
        $resumen = [
            'total_ingresos' => $cuenta->movimientos()->ingresos()->sum('monto'),
            'total_egresos' => $cuenta->movimientos()->egresos()->sum('monto'),
            'movimientos_mes' => $cuenta->movimientos()->delMes()->count(),
        ];

        return view('cuentas.show', compact('cuenta', 'resumen'));
    }

    public function edit(CuentaBancaria $cuenta)
    {
        $bancos = Banco::activos()->orderBy('nombre')->get();
        return view('cuentas.edit', compact('cuenta', 'bancos'));
    }

    public function update(Request $request, CuentaBancaria $cuenta)
    {
        $validated = $request->validate([
            'banco_id' => 'required|exists:bancos,id',
            'nombre' => 'required|string|max:255',
            'numero_cuenta' => 'nullable|string|max:50',
            'clabe' => 'nullable|string|max:20',
            'tipo' => 'required|in:banco,efectivo,tarjeta,inversion,otros',
            'moneda' => 'required|string|max:3',
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'descripcion' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['es_principal'] = $request->boolean('es_principal', false);
        $validated['activa'] = $request->boolean('activa', true);

        $cuenta->update($validated);

        // Si es principal, quitar de otras
        if ($cuenta->es_principal) {
            CuentaBancaria::where('id', '!=', $cuenta->id)
                ->update(['es_principal' => false]);
        }

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta bancaria actualizada exitosamente.');
    }

    public function destroy(CuentaBancaria $cuenta)
    {
        if ($cuenta->movimientos()->exists()) {
            return back()->with('error', 'No se puede eliminar la cuenta porque tiene movimientos asociados.');
        }

        $cuenta->delete();

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta bancaria eliminada exitosamente.');
    }

    /**
     * Marcar cuenta como principal
     */
    public function setPrincipal(CuentaBancaria $cuenta)
    {
        $cuenta->marcarComoPrincipal();

        return back()->with('success', 'Cuenta marcada como principal.');
    }

    /**
     * Recalcular saldo de la cuenta
     */
    public function recalcularSaldo(CuentaBancaria $cuenta)
    {
        $cuenta->recalcularSaldo();

        return back()->with('success', 'Saldo recalculado exitosamente.');
    }
}
