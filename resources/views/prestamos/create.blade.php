@extends('layouts.app')

@section('title', 'Nuevo Préstamo')
@section('page-title', 'Registrar Préstamo')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('prestamos.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Tipo de Préstamo -->
            <div x-data="{ tipo: '{{ old('tipo', 'otorgado') }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Préstamo *</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                           :class="tipo === 'otorgado' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                        <input type="radio" name="tipo" value="otorgado" x-model="tipo" class="sr-only">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full" :class="tipo === 'otorgado' ? 'bg-green-100' : 'bg-gray-100'">
                                <svg class="w-6 h-6" :class="tipo === 'otorgado' ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-medium" :class="tipo === 'otorgado' ? 'text-green-900' : 'text-gray-900'">Otorgado</span>
                                <span class="block text-xs text-gray-500">Dinero que presté</span>
                            </div>
                        </div>
                    </label>
                    <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                           :class="tipo === 'recibido' ? 'border-red-500 bg-red-50' : 'border-gray-300'">
                        <input type="radio" name="tipo" value="recibido" x-model="tipo" class="sr-only">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full" :class="tipo === 'recibido' ? 'bg-red-100' : 'bg-gray-100'">
                                <svg class="w-6 h-6" :class="tipo === 'recibido' ? 'text-red-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-medium" :class="tipo === 'recibido' ? 'text-red-900' : 'text-gray-900'">Recibido</span>
                                <span class="block text-xs text-gray-500">Dinero que me prestaron</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Descripción y Persona -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                    <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="Ej: Préstamo para vehículo">
                    @error('descripcion')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="persona_nombre" class="block text-sm font-medium text-gray-700 mb-1">Persona</label>
                    <input type="text" name="persona_nombre" id="persona_nombre" value="{{ old('persona_nombre') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="Nombre de la persona">
                </div>
            </div>

            <!-- Monto y Fechas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="monto_original" class="block text-sm font-medium text-gray-700 mb-1">Monto Original *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto_original" id="monto_original" value="{{ old('monto_original') }}" step="0.01" min="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    @error('monto_original')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <!-- Interés -->
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Configuración de Interés</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="tasa_interes" class="block text-sm font-medium text-gray-700 mb-1">Tasa de Interés (%)</label>
                        <input type="number" name="tasa_interes" id="tasa_interes" value="{{ old('tasa_interes', 0) }}" step="0.01" min="0"
                               class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="tipo_interes" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Interés</label>
                        <select name="tipo_interes" id="tipo_interes"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="fijo" {{ old('tipo_interes') == 'fijo' ? 'selected' : '' }}>Fijo</option>
                            <option value="mensual" {{ old('tipo_interes') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                            <option value="anual" {{ old('tipo_interes') == 'anual' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>
                    <div>
                        <label for="numero_pagos" class="block text-sm font-medium text-gray-700 mb-1">Número de Pagos</label>
                        <input type="number" name="numero_pagos" id="numero_pagos" value="{{ old('numero_pagos', 1) }}" min="1"
                               class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Para generar tabla de amortización</p>
                    </div>
                </div>
            </div>

            <!-- Cuenta asociada -->
            <div>
                <label for="cuenta_id" class="block text-sm font-medium text-gray-700 mb-1">Cuenta Asociada</label>
                <select name="cuenta_id" id="cuenta_id"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Sin cuenta asociada</option>
                    @foreach($cuentas ?? [] as $cuenta)
                        <option value="{{ $cuenta->id }}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                            {{ $cuenta->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Notas -->
            <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notas" id="notas" rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Notas adicionales...">{{ old('notas') }}</textarea>
            </div>

            <!-- Opciones -->
            <div class="border-t border-gray-200 pt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="generar_amortizacion" value="1" {{ old('generar_amortizacion', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700">Generar tabla de amortización automáticamente</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('prestamos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Registrar Préstamo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
