@extends('layouts.app')

@section('title', 'Editar Banco')
@section('page-title', 'Editar Banco')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('bancos.update', $banco) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Banco *</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $banco->nombre) }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="nombre_corto" class="block text-sm font-medium text-gray-700 mb-1">Nombre Corto</label>
                    <input type="text" name="nombre_corto" id="nombre_corto" value="{{ old('nombre_corto', $banco->nombre_corto) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                    <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $banco->codigo) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                <div class="flex items-center space-x-3">
                    <input type="color" name="color" id="color" value="{{ old('color', $banco->color ?? '#3B82F6') }}"
                           class="w-12 h-10 rounded border-gray-300">
                    <span class="text-sm text-gray-500">Color para identificar el banco</span>
                </div>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', $banco->activo) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700">Banco activo</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('bancos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Actualizar Banco
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
