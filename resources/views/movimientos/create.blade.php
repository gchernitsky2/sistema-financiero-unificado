@extends('layouts.app')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Registrar Movimiento')

@section('content')
@php
    $cuentaDefault = $cuentas->firstWhere('nombre', 'BRM MIFEL') ?? $cuentas->first();
@endphp

<div class="max-w-4xl mx-auto" x-data="movimientoForm({{ $cuentaDefault?->id ?? 'null' }}, {{ $cuentaDefault?->saldo_actual ?? 0 }})">
    <form action="{{ route('movimientos.store') }}" method="POST">
        @csrf

        {{-- Saldos en tiempo real --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Saldo Actual --}}
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Saldo Actual</p>
                <p class="text-2xl font-bold text-gray-900" x-text="'$' + saldoActual.toLocaleString('es-MX', {minimumFractionDigits: 2})"></p>
                <p class="text-xs text-gray-400" x-text="cuentaNombre"></p>
            </div>

            {{-- Movimiento --}}
            <div class="bg-white rounded-lg shadow p-4 border-l-4" :class="tipo === 'ingreso' ? 'border-green-500' : 'border-red-500'">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Movimiento</p>
                <p class="text-2xl font-bold" :class="tipo === 'ingreso' ? 'text-green-600' : 'text-red-600'"
                   x-text="(tipo === 'ingreso' ? '+' : '-') + '$' + parseFloat(monto || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})"></p>
                <p class="text-xs" :class="esProyectado ? 'text-orange-500' : 'text-gray-400'" x-text="esProyectado ? 'Proyectado' : 'Real'"></p>
            </div>

            {{-- Nuevo Saldo --}}
            <div class="bg-white rounded-lg shadow p-4 border-l-4" :class="nuevoSaldo >= 0 ? 'border-green-500' : 'border-red-500'">
                <p class="text-xs text-gray-500 uppercase tracking-wide" x-text="esProyectado ? 'Saldo Proyectado' : 'Nuevo Saldo'"></p>
                <p class="text-2xl font-bold" :class="nuevoSaldo >= 0 ? 'text-green-600' : 'text-red-600'"
                   x-text="'$' + nuevoSaldo.toLocaleString('es-MX', {minimumFractionDigits: 2})"></p>
                <p class="text-xs text-gray-400" x-text="esProyectado ? 'Después del movimiento proyectado' : 'Después del movimiento'"></p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 space-y-4">
                {{-- Tipo de Movimiento: Ingreso, Egreso, Traspaso --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tipo de Movimiento</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="tipo = 'ingreso'"
                                :class="tipo === 'ingreso' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-green-50'"
                                class="flex items-center justify-center py-3 px-4 border-2 rounded-lg transition-all font-medium">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            Ingreso
                        </button>
                        <button type="button" @click="tipo = 'egreso'"
                                :class="tipo === 'egreso' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-red-50'"
                                class="flex items-center justify-center py-3 px-4 border-2 rounded-lg transition-all font-medium">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                            </svg>
                            Egreso
                        </button>
                        <button type="button" @click="tipo = 'traspaso'"
                                :class="tipo === 'traspaso' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50'"
                                class="flex items-center justify-center py-3 px-4 border-2 rounded-lg transition-all font-medium">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Traspaso
                        </button>
                    </div>
                    <input type="hidden" name="tipo" :value="tipo === 'traspaso' ? 'egreso' : tipo">
                </div>

                {{-- Fila: Cuenta, Clasificación, Fecha --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Cuenta --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Cuenta</label>
                        <select name="cuenta_bancaria_id" x-model="cuentaId" @change="actualizarSaldoCuenta()"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            @foreach($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}"
                                        data-saldo="{{ $cuenta->saldo_actual }}"
                                        data-nombre="{{ $cuenta->nombre }}"
                                        {{ $cuentaDefault && $cuenta->id === $cuentaDefault->id ? 'selected' : '' }}>
                                    {{ $cuenta->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Clasificación: Real o Proyectado --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Clasificación</label>
                        <select name="clasificacion" x-model="clasificacion"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="real">Real (Ejecutado)</option>
                            <option value="proyectado">Proyectado</option>
                            <option value="programado">Programado</option>
                        </select>
                    </div>

                    {{-- Fecha --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                               class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Cuenta destino (solo para traspasos) --}}
                <div x-show="tipo === 'traspaso'" x-cloak>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Cuenta Destino</label>
                    <select name="cuenta_destino_id"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Seleccionar cuenta destino</option>
                        @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}">{{ $cuenta->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fila: Monto e IVA --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Monto --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Importe</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-medium">$</span>
                            <input type="number" name="monto" x-model="monto" step="0.01" min="0.01" required
                                   placeholder="0.00"
                                   class="w-full pl-8 py-2 px-3 border rounded-lg text-lg font-semibold focus:ring-2"
                                   :class="tipo === 'ingreso' ? 'border-green-300 focus:ring-green-500 focus:border-green-500' : 'border-red-300 focus:ring-red-500 focus:border-red-500'">
                        </div>
                    </div>

                    {{-- IVA --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">IVA</label>
                        <div class="flex">
                            <input type="number" name="iva" id="iva" value="{{ old('iva', '0') }}" step="0.01" min="0"
                                   class="w-full py-2 px-3 border border-gray-300 rounded-l-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            <button type="button" @click="calcularIVA()"
                                    class="px-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-xs text-gray-600 hover:bg-gray-200">
                                16%
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Descripción --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Descripción</label>
                    <input type="text" name="concepto" value="{{ old('concepto') }}" required
                           placeholder="Concepto del movimiento..."
                           class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                {{-- Fila: Categoría, Referencia, Beneficiario --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Categoría --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Categoría</label>
                        <select name="categoria_id"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Sin categoría</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Referencia --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Referencia</label>
                        <input type="text" name="referencia" value="{{ old('referencia') }}"
                               placeholder="No. referencia"
                               class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    {{-- Beneficiario --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Beneficiario</label>
                        <input type="text" name="beneficiario" value="{{ old('beneficiario') }}"
                               placeholder="Nombre del beneficiario"
                               class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Opciones adicionales compactas --}}
                <div class="flex flex-wrap items-center gap-4 pt-2 border-t border-gray-100">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="es_conciliado" value="1"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                        Conciliado
                    </label>
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="es_www" value="1"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                        WWW
                    </label>
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="es_urgente" value="1"
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 mr-2">
                        Urgente
                    </label>

                    {{-- Estado --}}
                    <input type="hidden" name="estado" :value="clasificacion === 'real' ? 'pendiente' : 'pendiente'">
                </div>

                {{-- Notas (colapsable) --}}
                <div x-data="{ mostrarNotas: false }">
                    <button type="button" @click="mostrarNotas = !mostrarNotas"
                            class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                        <svg class="w-4 h-4 mr-1 transition-transform" :class="mostrarNotas ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        Agregar notas
                    </button>
                    <div x-show="mostrarNotas" x-cloak class="mt-2">
                        <textarea name="notas" rows="2" placeholder="Notas adicionales..."
                                  class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">{{ old('notas') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                <a href="{{ route('movimientos.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm">
                    Cancelar
                </a>
                <div class="flex space-x-2">
                    <button type="submit" name="guardar_nuevo" value="1"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                        Guardar y Nuevo
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function movimientoForm(cuentaIdDefault, saldoDefault) {
    return {
        tipo: 'egreso',
        monto: '',
        cuentaId: cuentaIdDefault,
        saldoActual: parseFloat(saldoDefault) || 0,
        cuentaNombre: '',
        clasificacion: 'real',

        init() {
            this.actualizarSaldoCuenta();
        },

        get esProyectado() {
            return this.clasificacion === 'proyectado' || this.clasificacion === 'programado';
        },

        get nuevoSaldo() {
            const montoNum = parseFloat(this.monto) || 0;
            if (this.tipo === 'ingreso') {
                return this.saldoActual + montoNum;
            } else {
                return this.saldoActual - montoNum;
            }
        },

        actualizarSaldoCuenta() {
            const select = document.querySelector('select[name="cuenta_bancaria_id"]');
            const option = select.options[select.selectedIndex];
            if (option) {
                this.saldoActual = parseFloat(option.dataset.saldo) || 0;
                this.cuentaNombre = option.dataset.nombre || '';
            }
        },

        calcularIVA() {
            const montoNum = parseFloat(this.monto) || 0;
            const iva = montoNum * 0.16;
            document.getElementById('iva').value = iva.toFixed(2);
        }
    }
}
</script>
@endpush
@endsection
