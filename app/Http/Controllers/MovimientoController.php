<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\CuentaBancaria;
use App\Models\Categoria;
use App\Models\TipoMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Movimiento::with(['cuentaBancaria', 'categoria', 'tipoMovimiento']);

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
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('concepto', 'like', "%{$buscar}%")
                  ->orWhere('beneficiario', 'like', "%{$buscar}%")
                  ->orWhere('referencia', 'like', "%{$buscar}%")
                  ->orWhere('numero_documento', 'like', "%{$buscar}%");
            });
        }

        if ($request->boolean('solo_urgentes')) {
            $query->urgentes();
        }

        // Ordenar
        $query->orderByDesc('fecha')->orderByDesc('id');

        $movimientos = $query->paginate(30)->withQueryString();

        // Datos para filtros
        $cuentas = CuentaBancaria::activas()->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        // Totales filtrados
        $totales = [
            'ingresos' => (clone $query)->where('tipo', 'ingreso')->sum('monto'),
            'egresos' => (clone $query)->where('tipo', 'egreso')->sum('monto'),
        ];

        return view('movimientos.index', compact('movimientos', 'cuentas', 'categorias', 'totales'));
    }

    public function create()
    {
        $cuentas = CuentaBancaria::activas()->with('banco')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();
        $tiposMovimiento = TipoMovimiento::activos()->orderBy('nombre')->get();

        return view('movimientos.create', compact('cuentas', 'categorias', 'tiposMovimiento'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'tipo_movimiento_id' => 'nullable|exists:tipos_movimiento,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'fecha' => 'required|date',
            'fecha_valor' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:255',
            'beneficiario' => 'nullable|string|max:255',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:ingreso,egreso',
            'clasificacion' => 'nullable|in:real,proyectado,programado',
            'tiene_iva' => 'boolean',
            'porcentaje_iva' => 'nullable|numeric|min:0|max:100',
            'estado' => 'nullable|in:pendiente,conciliado,cancelado',
            'es_urgente' => 'boolean',
            'es_www' => 'boolean',
            'es_prestamo_socio' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['clasificacion'] = $request->input('clasificacion', 'real');
        $validated['estado'] = $request->input('estado', 'pendiente');
        $validated['tiene_iva'] = $request->boolean('tiene_iva', false);
        $validated['es_urgente'] = $request->boolean('es_urgente', false);
        $validated['es_www'] = $request->boolean('es_www', false);
        $validated['es_prestamo_socio'] = $request->boolean('es_prestamo_socio', false);
        $validated['pagado'] = true;
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $movimiento = new Movimiento($validated);

            // Calcular IVA si aplica
            if ($movimiento->tiene_iva) {
                $movimiento->calcularIva();
            }

            $movimiento->save();
        });

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento registrado exitosamente.');
    }

    public function show(Movimiento $movimiento)
    {
        $movimiento->load(['cuentaBancaria.banco', 'categoria', 'tipoMovimiento', 'conciliacion']);

        return view('movimientos.show', compact('movimiento'));
    }

    public function edit(Movimiento $movimiento)
    {
        // No permitir editar movimientos conciliados
        if ($movimiento->estado === 'conciliado') {
            return back()->with('error', 'No se puede editar un movimiento conciliado.');
        }

        $cuentas = CuentaBancaria::activas()->with('banco')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();
        $tiposMovimiento = TipoMovimiento::activos()->orderBy('nombre')->get();

        return view('movimientos.edit', compact('movimiento', 'cuentas', 'categorias', 'tiposMovimiento'));
    }

    public function update(Request $request, Movimiento $movimiento)
    {
        // No permitir editar movimientos conciliados
        if ($movimiento->estado === 'conciliado') {
            return back()->with('error', 'No se puede editar un movimiento conciliado.');
        }

        $validated = $request->validate([
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'tipo_movimiento_id' => 'nullable|exists:tipos_movimiento,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'fecha' => 'required|date',
            'fecha_valor' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:255',
            'beneficiario' => 'nullable|string|max:255',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:ingreso,egreso',
            'clasificacion' => 'nullable|in:real,proyectado,programado',
            'tiene_iva' => 'boolean',
            'porcentaje_iva' => 'nullable|numeric|min:0|max:100',
            'estado' => 'nullable|in:pendiente,conciliado,cancelado',
            'es_urgente' => 'boolean',
            'es_www' => 'boolean',
            'es_prestamo_socio' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['clasificacion'] = $request->input('clasificacion', $movimiento->clasificacion ?? 'real');
        $validated['tiene_iva'] = $request->boolean('tiene_iva', false);
        $validated['es_urgente'] = $request->boolean('es_urgente', false);
        $validated['es_www'] = $request->boolean('es_www', false);
        $validated['es_prestamo_socio'] = $request->boolean('es_prestamo_socio', false);

        DB::transaction(function () use ($movimiento, $validated) {
            $movimiento->fill($validated);

            // Recalcular IVA si aplica
            if ($movimiento->tiene_iva) {
                $movimiento->calcularIva();
            }

            $movimiento->save();
        });

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento actualizado exitosamente.');
    }

    public function destroy(Movimiento $movimiento)
    {
        if ($movimiento->estado === 'conciliado') {
            return back()->with('error', 'No se puede eliminar un movimiento conciliado.');
        }

        $movimiento->delete();

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento eliminado exitosamente.');
    }

    /**
     * Crear múltiples movimientos
     */
    public function createMultiple()
    {
        $cuentas = CuentaBancaria::activas()->with('banco')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('movimientos.create-multiple', compact('cuentas', 'categorias'));
    }

    public function storeMultiple(Request $request)
    {
        $validated = $request->validate([
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'movimientos' => 'required|array|min:1',
            'movimientos.*.fecha' => 'required|date',
            'movimientos.*.concepto' => 'required|string|max:500',
            'movimientos.*.monto' => 'required|numeric|min:0.01',
            'movimientos.*.tipo' => 'required|in:ingreso,egreso',
            'movimientos.*.categoria_id' => 'nullable|exists:categorias,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['movimientos'] as $mov) {
                Movimiento::create([
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'],
                    'fecha' => $mov['fecha'],
                    'concepto' => $mov['concepto'],
                    'monto' => $mov['monto'],
                    'tipo' => $mov['tipo'],
                    'categoria_id' => $mov['categoria_id'] ?? null,
                    'estado' => 'pendiente',
                    'pagado' => true,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimientos registrados exitosamente.');
    }

    /**
     * Marcar movimiento como pagado
     */
    public function marcarPagado(Movimiento $movimiento)
    {
        $movimiento->marcarComoPagado();

        return back()->with('success', 'Movimiento marcado como pagado.');
    }
}
