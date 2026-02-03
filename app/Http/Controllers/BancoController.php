<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = Banco::withCount('cuentasActivas')
            ->orderBy('nombre')
            ->paginate(20);

        return view('bancos.index', compact('bancos'));
    }

    public function create()
    {
        return view('bancos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'swift' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->boolean('activo', true);

        Banco::create($validated);

        return redirect()->route('bancos.index')
            ->with('success', 'Banco creado exitosamente.');
    }

    public function show(Banco $banco)
    {
        $banco->load(['cuentas' => function ($query) {
            $query->orderByDesc('es_principal')->orderBy('nombre');
        }]);

        return view('bancos.show', compact('banco'));
    }

    public function edit(Banco $banco)
    {
        return view('bancos.edit', compact('banco'));
    }

    public function update(Request $request, Banco $banco)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'swift' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->boolean('activo', true);

        $banco->update($validated);

        return redirect()->route('bancos.index')
            ->with('success', 'Banco actualizado exitosamente.');
    }

    public function destroy(Banco $banco)
    {
        if ($banco->cuentas()->exists()) {
            return back()->with('error', 'No se puede eliminar el banco porque tiene cuentas asociadas.');
        }

        $banco->delete();

        return redirect()->route('bancos.index')
            ->with('success', 'Banco eliminado exitosamente.');
    }

    /**
     * Actualizar campo individual (AJAX)
     */
    public function updateField(Request $request, Banco $banco)
    {
        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['nombre', 'codigo', 'swift', 'direccion', 'telefono', 'email', 'sitio_web', 'activo'];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['error' => 'Campo no permitido'], 400);
        }

        $banco->update([$field => $value]);

        return response()->json(['success' => true, 'message' => 'Campo actualizado']);
    }
}
