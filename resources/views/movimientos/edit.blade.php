@extends('layouts.app')

@section('title', 'Editar Movimiento')
@section('page-title', 'Editar Movimiento')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('movimientos.update', $movimiento) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fecha -->
                <div>
                    <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha" id="fecha" value="{{ old('fecha', $movimiento->fecha->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    @error('fecha')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cuenta -->
                <div>
                    <label for="cuenta_id" class="block text-sm font-medium text-gray-700 mb-1">Cuenta *</label>
                    <select name="cuenta_id" id="cuenta_id" required
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Seleccionar cuenta</option>
                        @foreach($cuentas ?? [] as $cuenta)
                            <option value="{{ $cuenta->id }}" {{ old('cuenta_id', $movimiento->cuenta_id) == $cuenta->id ? 'selected' : '' }}>
                                {{ $cuenta->nombre }} ({{ $cuenta->banco->nombre ?? 'Sin banco' }})
                            </option>
                        @endforeach
                    </select>
                    @error('cuenta_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Tipo de Movimiento -->
            @php
                $tipoActual = $movimiento->cargo > 0 ? 'cargo' : 'abono';
                $montoActual = $movimiento->cargo > 0 ? $movimiento->cargo : $movimiento->abono;
            @endphp
            <div x-data="{ tipo: '{{ old('tipo', $tipoActual) }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento *</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="tipo" value="cargo" x-model="tipo" class="text-red-600 focus:ring-red-500">
                        <span class="ml-2 text-sm text-gray-700">Cargo (Egreso)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipo" value="abono" x-model="tipo" class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700">Abono (Ingreso)</span>
                    </label>
                </div>

                <!-- Monto -->
                <div class="mt-4">
                    <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto" id="monto" value="{{ old('monto', $montoActual) }}" step="0.01" min="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                               :class="tipo === 'cargo' ? 'border-red-300' : 'border-green-300'">
                    </div>
                    @error('monto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Descripción -->
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion', $movimiento->descripcion) }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                @error('descripcion')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Categoría -->
                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="categoria_id" id="categoria_id"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sin categoría</option>
                        @foreach($categorias ?? [] as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('categoria_id', $movimiento->categoria_id) == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Referencia -->
                <div>
                    <label for="referencia" class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" name="referencia" id="referencia" value="{{ old('referencia', $movimiento->referencia) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <!-- IVA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="iva" class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="iva" id="iva" value="{{ old('iva', $movimiento->iva ?? 0) }}" step="0.01" min="0"
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>

                <div class="flex items-end">
                    <button type="button" onclick="calcularIva()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300">
                        Calcular IVA 16%
                    </button>
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notas" id="notas" rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">{{ old('notas', $movimiento->notas) }}</textarea>
            </div>

            <!-- Opciones adicionales -->
            <div class="border-t border-gray-200 pt-4">
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="es_conciliado" value="1" {{ old('es_conciliado', $movimiento->es_conciliado) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">Conciliado</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="es_www" value="1" {{ old('es_www', $movimiento->es_www) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">WWW (Web)</span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('movimientos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Actualizar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function calcularIva() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const iva = monto * 0.16;
    document.getElementById('iva').value = iva.toFixed(2);
}
</script>
@endpush
@endsection
