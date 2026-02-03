@extends('layouts.app')

@section('title', 'Nuevo Pago Programado')
@section('page-title', 'Programar Pago')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('pagos-programados.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Concepto -->
            <div>
                <label for="concepto" class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                <input type="text" name="concepto" id="concepto" value="{{ old('concepto') }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Ej: Pago de luz, Renta, etc.">
                @error('concepto')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Beneficiario -->
                <div>
                    <label for="beneficiario" class="block text-sm font-medium text-gray-700 mb-1">Beneficiario</label>
                    <input type="text" name="beneficiario" id="beneficiario" value="{{ old('beneficiario') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="Nombre del beneficiario">
                </div>

                <!-- Cuenta -->
                <div>
                    <label for="cuenta_id" class="block text-sm font-medium text-gray-700 mb-1">Cuenta a debitar</label>
                    <select name="cuenta_id" id="cuenta_id"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Seleccionar cuenta</option>
                        @foreach($cuentas ?? [] as $cuenta)
                            <option value="{{ $cuenta->id }}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                                {{ $cuenta->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto -->
                <div>
                    <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto" id="monto" value="{{ old('monto') }}" step="0.01" min="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    @error('monto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha Vencimiento -->
                <div>
                    <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento *</label>
                    <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    @error('fecha_vencimiento')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Categoría -->
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select name="categoria_id" id="categoria_id"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Sin categoría</option>
                    @foreach($categorias ?? [] as $categoria)
                        <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Recurrencia -->
            <div class="border-t border-gray-200 pt-4" x-data="{ esRecurrente: {{ old('es_recurrente') ? 'true' : 'false' }} }">
                <label class="flex items-center mb-4">
                    <input type="checkbox" name="es_recurrente" value="1" x-model="esRecurrente"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm font-medium text-gray-700">Este pago es recurrente</span>
                </label>

                <div x-show="esRecurrente" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="frecuencia" class="block text-sm font-medium text-gray-700 mb-1">Frecuencia</label>
                        <select name="frecuencia" id="frecuencia"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="mensual" {{ old('frecuencia') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                            <option value="quincenal" {{ old('frecuencia') == 'quincenal' ? 'selected' : '' }}>Quincenal</option>
                            <option value="semanal" {{ old('frecuencia') == 'semanal' ? 'selected' : '' }}>Semanal</option>
                            <option value="bimestral" {{ old('frecuencia') == 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                            <option value="trimestral" {{ old('frecuencia') == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                            <option value="semestral" {{ old('frecuencia') == 'semestral' ? 'selected' : '' }}>Semestral</option>
                            <option value="anual" {{ old('frecuencia') == 'anual' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>
                    <div>
                        <label for="fecha_fin_recurrencia" class="block text-sm font-medium text-gray-700 mb-1">Fecha fin (opcional)</label>
                        <input type="date" name="fecha_fin_recurrencia" id="fecha_fin_recurrencia" value="{{ old('fecha_fin_recurrencia') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Prioridad manual -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="prioridad_manual" class="block text-sm font-medium text-gray-700 mb-1">Prioridad Manual (1-100)</label>
                    <input type="number" name="prioridad_manual" id="prioridad_manual" value="{{ old('prioridad_manual', 50) }}" min="1" max="100"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500">100 = máxima prioridad. Afecta el cálculo IA.</p>
                </div>

                <div>
                    <label for="porcentaje_mora" class="block text-sm font-medium text-gray-700 mb-1">% Mora por día</label>
                    <input type="number" name="porcentaje_mora" id="porcentaje_mora" value="{{ old('porcentaje_mora', 0) }}" step="0.01" min="0"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notas" id="notas" rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Notas adicionales...">{{ old('notas') }}</textarea>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('pagos-programados.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Programar Pago
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
