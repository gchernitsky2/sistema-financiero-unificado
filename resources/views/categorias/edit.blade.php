@extends('layouts.app')

@section('title', 'Editar Categoría')
@section('page-title', 'Editar Categoría')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('categorias.update', $categoria) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $categoria->nombre) }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                    <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $categoria->codigo) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select name="tipo" id="tipo" required
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="egreso" {{ old('tipo', $categoria->tipo) == 'egreso' ? 'selected' : '' }}>Egreso</option>
                        <option value="ingreso" {{ old('tipo', $categoria->tipo) == 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                        <option value="ambos" {{ old('tipo', $categoria->tipo) == 'ambos' ? 'selected' : '' }}>Ambos</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría Padre</label>
                <select name="parent_id" id="parent_id"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Sin categoría padre</option>
                    @foreach($categoriasPadre ?? [] as $padre)
                        <option value="{{ $padre->id }}" {{ old('parent_id', $categoria->parent_id) == $padre->id ? 'selected' : '' }}>
                            {{ $padre->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $categoria->color ?? '#3B82F6') }}"
                           class="w-full h-10 rounded border-gray-300">
                </div>
                <div>
                    <label for="icono" class="block text-sm font-medium text-gray-700 mb-1">Icono (emoji)</label>
                    <input type="text" name="icono" id="icono" value="{{ old('icono', $categoria->icono) }}" maxlength="10"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="2"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">{{ old('descripcion', $categoria->descripcion) }}</textarea>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="activa" value="1" {{ old('activa', $categoria->activa) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700">Categoría activa</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('categorias.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Actualizar Categoría
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
