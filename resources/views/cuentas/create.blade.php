@extends('layouts.app')

@section('title', 'Nueva Cuenta')
@section('page-title', 'Crear Cuenta Bancaria')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('cuentas.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Cuenta *</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Ej: Cuenta Principal, Ahorros, etc.">
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Banco -->
                <div>
                    <label for="banco_id" class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                    <select name="banco_id" id="banco_id"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sin banco</option>
                        @foreach($bancos ?? [] as $banco)
                            <option value="{{ $banco->id }}" {{ old('banco_id') == $banco->id ? 'selected' : '' }}>
                                {{ $banco->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tipo -->
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cuenta *</label>
                    <select name="tipo" id="tipo" required
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="banco" {{ old('tipo') == 'banco' ? 'selected' : '' }}>Cuenta Bancaria</option>
                        <option value="efectivo" {{ old('tipo') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="tarjeta" {{ old('tipo') == 'tarjeta' ? 'selected' : '' }}>Tarjeta de Crédito</option>
                        <option value="inversion" {{ old('tipo') == 'inversion' ? 'selected' : '' }}>Inversión</option>
                        <option value="otros" {{ old('tipo') == 'otros' ? 'selected' : '' }}>Otros</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Número de Cuenta -->
                <div>
                    <label for="numero_cuenta" class="block text-sm font-medium text-gray-700 mb-1">Número de Cuenta</label>
                    <input type="text" name="numero_cuenta" id="numero_cuenta" value="{{ old('numero_cuenta') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="0000000000">
                </div>

                <!-- CLABE -->
                <div>
                    <label for="clabe" class="block text-sm font-medium text-gray-700 mb-1">CLABE</label>
                    <input type="text" name="clabe" id="clabe" value="{{ old('clabe') }}" maxlength="18"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="18 dígitos">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Moneda -->
                <div>
                    <label for="moneda" class="block text-sm font-medium text-gray-700 mb-1">Moneda *</label>
                    <select name="moneda" id="moneda" required
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="MXN" {{ old('moneda', 'MXN') == 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                        <option value="USD" {{ old('moneda') == 'USD' ? 'selected' : '' }}>USD - Dólar Americano</option>
                        <option value="EUR" {{ old('moneda') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                    </select>
                </div>

                <!-- Saldo Inicial -->
                <div>
                    <label for="saldo_inicial" class="block text-sm font-medium text-gray-700 mb-1">Saldo Inicial *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="saldo_inicial" id="saldo_inicial" value="{{ old('saldo_inicial', '0') }}" step="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    @error('saldo_inicial')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Descripción -->
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="2"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Descripción opcional...">{{ old('descripcion') }}</textarea>
            </div>

            <!-- Opciones -->
            <div class="border-t border-gray-200 pt-4">
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="es_principal" value="1" {{ old('es_principal') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">Establecer como cuenta principal</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="activa" value="1" {{ old('activa', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">Cuenta activa</span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('cuentas.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Crear Cuenta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
