@extends('layouts.app')

@section('title', 'Nuevo Recordatorio')
@section('page-title', 'Crear Recordatorio')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('recordatorios.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" id="titulo" value="{{ old('titulo') }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Ej: Pagar tarjeta de crédito">
                @error('titulo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                <select name="tipo" id="tipo" required
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    <option value="general" {{ old('tipo') == 'general' ? 'selected' : '' }}>General</option>
                    <option value="pago" {{ old('tipo') == 'pago' ? 'selected' : '' }}>Pago</option>
                    <option value="prestamo" {{ old('tipo') == 'prestamo' ? 'selected' : '' }}>Préstamo</option>
                    <option value="meta" {{ old('tipo') == 'meta' ? 'selected' : '' }}>Meta</option>
                    <option value="movimiento" {{ old('tipo') == 'movimiento' ? 'selected' : '' }}>Movimiento</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="fecha_recordatorio" class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha_recordatorio" id="fecha_recordatorio" value="{{ old('fecha_recordatorio') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    @error('fecha_recordatorio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="hora_recordatorio" class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                    <input type="time" name="hora_recordatorio" id="hora_recordatorio" value="{{ old('hora_recordatorio') }}"
                           class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label for="mensaje" class="block text-sm font-medium text-gray-700 mb-1">Mensaje</label>
                <textarea name="mensaje" id="mensaje" rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Detalles adicionales del recordatorio...">{{ old('mensaje') }}</textarea>
            </div>

            <div>
                <label for="dias_anticipacion" class="block text-sm font-medium text-gray-700 mb-1">Días de anticipación</label>
                <input type="number" name="dias_anticipacion" id="dias_anticipacion" value="{{ old('dias_anticipacion', 0) }}" min="0" max="30"
                       class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                <p class="mt-1 text-xs text-gray-500">Cuántos días antes mostrar el recordatorio</p>
            </div>

            <div class="border-t border-gray-200 pt-4" x-data="{ repetir: {{ old('repetir') ? 'true' : 'false' }} }">
                <label class="flex items-center mb-4">
                    <input type="checkbox" name="repetir" value="1" x-model="repetir"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700">Repetir recordatorio</span>
                </label>

                <div x-show="repetir" x-cloak>
                    <label for="frecuencia_repeticion" class="block text-sm font-medium text-gray-700 mb-1">Frecuencia</label>
                    <select name="frecuencia_repeticion" id="frecuencia_repeticion"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="diario" {{ old('frecuencia_repeticion') == 'diario' ? 'selected' : '' }}>Diario</option>
                        <option value="semanal" {{ old('frecuencia_repeticion') == 'semanal' ? 'selected' : '' }}>Semanal</option>
                        <option value="mensual" {{ old('frecuencia_repeticion', 'mensual') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                        <option value="anual" {{ old('frecuencia_repeticion') == 'anual' ? 'selected' : '' }}>Anual</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Notificaciones</p>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="notificar_sistema" value="1" {{ old('notificar_sistema', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">Mostrar en el sistema</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="notificar_email" value="1" {{ old('notificar_email') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">Enviar por email</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('recordatorios.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Crear Recordatorio
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
