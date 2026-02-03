@extends('layouts.app')

@section('title', 'Nueva Meta')
@section('page-title', 'Crear Meta Financiera')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('metas.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Meta *</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Ej: Fondo de emergencia, Vacaciones, etc.">
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="2"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Describe tu meta...">{{ old('descripcion') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto Objetivo -->
                <div>
                    <label for="monto_objetivo" class="block text-sm font-medium text-gray-700 mb-1">Monto Objetivo *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto_objetivo" id="monto_objetivo" value="{{ old('monto_objetivo') }}" step="0.01" min="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    @error('monto_objetivo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Monto Inicial -->
                <div>
                    <label for="monto_actual" class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto_actual" id="monto_actual" value="{{ old('monto_actual', 0) }}" step="0.01" min="0"
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fecha Objetivo -->
                <div>
                    <label for="fecha_objetivo" class="block text-sm font-medium text-gray-700 mb-1">Fecha Objetivo</label>
                    <input type="date" name="fecha_objetivo" id="fecha_objetivo" value="{{ old('fecha_objetivo') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Categoría -->
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" id="categoria"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sin categoría</option>
                        <option value="ahorro" {{ old('categoria') == 'ahorro' ? 'selected' : '' }}>Ahorro</option>
                        <option value="emergencia" {{ old('categoria') == 'emergencia' ? 'selected' : '' }}>Fondo de emergencia</option>
                        <option value="viaje" {{ old('categoria') == 'viaje' ? 'selected' : '' }}>Viaje</option>
                        <option value="vehiculo" {{ old('categoria') == 'vehiculo' ? 'selected' : '' }}>Vehículo</option>
                        <option value="vivienda" {{ old('categoria') == 'vivienda' ? 'selected' : '' }}>Vivienda</option>
                        <option value="educacion" {{ old('categoria') == 'educacion' ? 'selected' : '' }}>Educación</option>
                        <option value="retiro" {{ old('categoria') == 'retiro' ? 'selected' : '' }}>Retiro</option>
                        <option value="otro" {{ old('categoria') == 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
            </div>

            <!-- Prioridad -->
            <div>
                <label for="prioridad" class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                <select name="prioridad" id="prioridad"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <option value="baja" {{ old('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                    <option value="media" {{ old('prioridad', 'media') == 'media' ? 'selected' : '' }}>Media</option>
                    <option value="alta" {{ old('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                </select>
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
                <p class="mt-1 text-xs text-gray-500">Si asocias una cuenta, los aportes se reflejarán automáticamente</p>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('metas.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Crear Meta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
