<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::withCount('movimientos')
            ->orderBy('tipo')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(30);

        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        $categoriasPadre = Categoria::principales()->activas()->orderBy('nombre')->get();

        return view('categorias.create', compact('categoriasPadre'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'tipo' => 'required|in:ingreso,egreso,ambos',
            'color' => 'nullable|string|max:7',
            'icono' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categorias,id',
            'orden' => 'nullable|integer|min:0',
            'activa' => 'boolean',
        ]);

        $validated['activa'] = $request->boolean('activa', true);

        Categoria::create($validated);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Categoria $categoria)
    {
        $categoriasPadre = Categoria::principales()
            ->where('id', '!=', $categoria->id)
            ->activas()
            ->orderBy('nombre')
            ->get();

        return view('categorias.edit', compact('categoria', 'categoriasPadre'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'tipo' => 'required|in:ingreso,egreso,ambos',
            'color' => 'nullable|string|max:7',
            'icono' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categorias,id',
            'orden' => 'nullable|integer|min:0',
            'activa' => 'boolean',
        ]);

        $validated['activa'] = $request->boolean('activa', true);

        $categoria->update($validated);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->es_sistema) {
            return back()->with('error', 'No se puede eliminar una categoría del sistema.');
        }

        if ($categoria->movimientos()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene movimientos asociados.');
        }

        if ($categoria->children()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene subcategorías.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}
