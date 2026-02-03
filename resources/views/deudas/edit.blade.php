@extends('layouts.app')

@section('title', 'Editar Deuda')
@section('page-title', 'Editar Deuda')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('deudas.update', $deuda) }}" method="POST" class="bg-white rounded-lg shadow">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-6">
            {{-- Indicador de tipo (solo lectura) --}}
            <div class="flex items-center p-3 rounded-lg {{ $deuda->tipo === 'receivable' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <div class="p-2 rounded-full {{ $deuda->tipo === 'receivable' ? 'bg-green-100' : 'bg-red-100' }}">
                    @if($deuda->tipo === 'receivable')
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    @endif
                </div>
                <div class="ml-3">
                    <p class="font-medium {{ $deuda->tipo === 'receivable' ? 'text-green-800' : 'text-red-800' }}">
                        {{ $deuda->tipo_label }}
                    </p>
                    <p class="text-sm {{ $deuda->tipo === 'receivable' ? 'text-green-600' : 'text-red-600' }}">
                        Estado: {{ $deuda->estado_label }}
                    </p>
                </div>
            </div>

            {{-- Datos de la persona --}}
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">
                    {{ $deuda->tipo === 'receivable' ? 'Deudor' : 'Acreedor' }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="persona_nombre" value="{{ old('persona_nombre', $deuda->persona_nombre) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500 @error('persona_nombre') border-red-500 @enderror">
                        @error('persona_nombre')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="persona_telefono" value="{{ old('persona_telefono', $deuda->persona_telefono) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="persona_email" value="{{ old('persona_email', $deuda->persona_email) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            {{-- Datos de la deuda --}}
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Detalle de la Deuda</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                        <textarea name="descripcion" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500 @error('descripcion') border-red-500 @enderror">{{ old('descripcion', $deuda->descripcion) }}</textarea>
                        @error('descripcion')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto Original *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                <input type="number" name="monto_original" value="{{ old('monto_original', $deuda->monto_original) }}" step="0.01" min="0.01" required
                                       class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-primary-500 focus:border-primary-500 @error('monto_original') border-red-500 @enderror">
                            </div>
                            @error('monto_original')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @if($deuda->monto_pagado > 0)
                            <p class="text-xs text-gray-500 mt-1">Ya pagado: ${{ number_format($deuda->monto_pagado, 2) }}</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                            <select name="prioridad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="0" {{ old('prioridad', $deuda->prioridad) == 0 ? 'selected' : '' }}>Normal</option>
                                <option value="1" {{ old('prioridad', $deuda->prioridad) == 1 ? 'selected' : '' }}>Alta</option>
                                <option value="-1" {{ old('prioridad', $deuda->prioridad) == -1 ? 'selected' : '' }}>Baja</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
                        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $deuda->fecha_vencimiento?->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            {{-- Configuración adicional --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Configuración</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta bancaria asociada</label>
                        <select name="cuenta_bancaria_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Sin asociar</option>
                            @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" {{ old('cuenta_bancaria_id', $deuda->cuenta_bancaria_id) == $cuenta->id ? 'selected' : '' }}>
                                {{ $cuenta->nombre_completo }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="categoria_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Sin categoría</option>
                            @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('categoria_id', $deuda->categoria_id) == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas adicionales</label>
                    <textarea name="notas" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">{{ old('notas', $deuda->notas) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Botones --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <form action="{{ route('deudas.destroy', $deuda) }}" method="POST" class="inline"
                  onsubmit="return confirm('¿Estás seguro de eliminar esta deuda? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                    Eliminar deuda
                </button>
            </form>

            <div class="flex space-x-3">
                <a href="{{ route('deudas.show', $deuda) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
