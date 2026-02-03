@extends('layouts.app')

@section('title', 'Gestión de Deudas')
@section('page-title', 'Gestión de Deudas')

@section('header-actions')
<a href="{{ route('deudas.create', ['type' => 'payable']) }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 mr-2">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Por Pagar
</a>
<a href="{{ route('deudas.create', ['type' => 'receivable']) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Por Cobrar
</a>
@endsection

@section('content')
<div x-data="deudasApp()">
    {{-- Resumen de estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Por Cobrar --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Por Cobrar</p>
                    <p class="text-2xl font-bold">${{ number_format($estadisticas['por_cobrar']['total_pendiente'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm text-green-100">
                <span>{{ $estadisticas['por_cobrar']['pendientes'] ?? 0 }} pendientes</span>
                <span class="mx-2">|</span>
                <span>{{ $estadisticas['por_cobrar']['vencidas'] ?? 0 }} vencidas</span>
            </div>
        </div>

        {{-- Por Pagar --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">Por Pagar</p>
                    <p class="text-2xl font-bold">${{ number_format($estadisticas['por_pagar']['total_pendiente'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-red-400 bg-opacity-30 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm text-red-100">
                <span>{{ $estadisticas['por_pagar']['pendientes'] ?? 0 }} pendientes</span>
                <span class="mx-2">|</span>
                <span>{{ $estadisticas['por_pagar']['vencidas'] ?? 0 }} vencidas</span>
            </div>
        </div>

        {{-- Balance --}}
        @php
            $balance = ($estadisticas['por_cobrar']['total_pendiente'] ?? 0) - ($estadisticas['por_pagar']['total_pendiente'] ?? 0);
        @endphp
        <div class="bg-gradient-to-br {{ $balance >= 0 ? 'from-blue-500 to-blue-600' : 'from-orange-500 to-orange-600' }} rounded-lg shadow p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Balance Neto</p>
                    <p class="text-2xl font-bold">{{ $balance >= 0 ? '+' : '' }}${{ number_format($balance, 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-sm {{ $balance >= 0 ? 'text-blue-100' : 'text-orange-100' }}">
                {{ $balance >= 0 ? 'A tu favor' : 'En contra' }}
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form action="{{ route('deudas.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            {{-- Filtro por tipo --}}
            <div class="flex rounded-lg overflow-hidden border border-gray-300">
                <a href="{{ route('deudas.index', ['type' => 'all', 'status' => $estado]) }}"
                   class="px-4 py-2 text-sm font-medium {{ $tipo === 'all' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Todas
                </a>
                <a href="{{ route('deudas.index', ['type' => 'receivable', 'status' => $estado]) }}"
                   class="px-4 py-2 text-sm font-medium border-l {{ $tipo === 'receivable' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Por Cobrar
                </a>
                <a href="{{ route('deudas.index', ['type' => 'payable', 'status' => $estado]) }}"
                   class="px-4 py-2 text-sm font-medium border-l {{ $tipo === 'payable' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Por Pagar
                </a>
            </div>

            {{-- Filtro por estado --}}
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="all" {{ $estado === 'all' ? 'selected' : '' }}>Todos los estados</option>
                <option value="pendiente" {{ $estado === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                <option value="parcial" {{ $estado === 'parcial' ? 'selected' : '' }}>Pago Parcial</option>
                <option value="vencido" {{ $estado === 'vencido' ? 'selected' : '' }}>Vencidas</option>
                <option value="pagado" {{ $estado === 'pagado' ? 'selected' : '' }}>Pagadas</option>
            </select>
            <input type="hidden" name="type" value="{{ $tipo }}">

            {{-- Búsqueda --}}
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <input type="text" name="search" value="{{ $busqueda }}"
                           placeholder="Buscar por nombre o descripción..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm">
                Buscar
            </button>

            @if($busqueda)
            <a href="{{ route('deudas.index', ['type' => $tipo, 'status' => $estado]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                Limpiar
            </a>
            @endif
        </form>
    </div>

    {{-- Deudas urgentes --}}
    @if($urgentes->isNotEmpty())
    <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-4 mb-6">
        <div class="flex items-center mb-2">
            <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h4 class="font-semibold text-orange-800">Deudas Próximas a Vencer</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($urgentes as $urgente)
            <a href="{{ route('deudas.show', $urgente) }}" class="bg-white rounded-lg p-3 border border-orange-200 hover:border-orange-400 transition-colors">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">{{ $urgente->persona_nombre }}</span>
                    <span class="text-xs px-2 py-1 rounded-full bg-{{ $urgente->tipo_color }}-100 text-{{ $urgente->tipo_color }}-800">
                        {{ $urgente->tipo_label }}
                    </span>
                </div>
                <p class="text-lg font-bold {{ $urgente->tipo === 'receivable' ? 'text-green-600' : 'text-red-600' }}">
                    ${{ number_format($urgente->monto_original - $urgente->monto_pagado, 2) }}
                </p>
                <p class="text-xs text-orange-600">
                    Vence {{ $urgente->fecha_vencimiento->diffForHumans() }}
                </p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Listado de deudas --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Persona/Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pendiente</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($deudas as $deuda)
                    <tr class="hover:bg-gray-50 {{ $deuda->esta_vencida ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4">
                            <div>
                                <a href="{{ route('deudas.show', $deuda) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                    {{ $deuda->persona_nombre }}
                                </a>
                                <p class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($deuda->descripcion, 50) }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $deuda->tipo_color }}-100 text-{{ $deuda->tipo_color }}-800">
                                {{ $deuda->tipo_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            ${{ number_format($deuda->monto_original, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-semibold {{ $deuda->tipo === 'receivable' ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($deuda->monto_original - $deuda->monto_pagado, 2) }}
                            </span>
                            @if($deuda->monto_pagado > 0)
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div class="bg-{{ $deuda->tipo_color }}-500 h-1.5 rounded-full" style="width: {{ $deuda->porcentaje_pagado }}%"></div>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            @if($deuda->fecha_vencimiento)
                                <span class="{{ $deuda->esta_vencida ? 'text-red-600 font-semibold' : ($deuda->es_urgente ? 'text-orange-600' : 'text-gray-500') }}">
                                    {{ $deuda->fecha_vencimiento->format('d/m/Y') }}
                                </span>
                                @if($deuda->dias_para_vencer !== null && !$deuda->esta_vencida)
                                <p class="text-xs {{ $deuda->es_urgente ? 'text-orange-500' : 'text-gray-400' }}">
                                    {{ $deuda->dias_para_vencer }} días
                                </p>
                                @endif
                            @else
                                <span class="text-gray-400">Sin fecha</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $deuda->estado_color }}-100 text-{{ $deuda->estado_color }}-800">
                                {{ $deuda->estado_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @if($deuda->estado !== 'pagado' && $deuda->estado !== 'cancelado')
                                <button @click="abrirModalPago({{ $deuda->id }}, '{{ $deuda->persona_nombre }}', {{ $deuda->monto_original - $deuda->monto_pagado }})"
                                        class="text-green-600 hover:text-green-800" title="Registrar pago">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                @endif
                                <a href="{{ route('deudas.show', $deuda) }}" class="text-primary-600 hover:text-primary-800" title="Ver detalle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('deudas.edit', $deuda) }}" class="text-gray-600 hover:text-gray-800" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-gray-500">No hay deudas registradas</p>
                            <div class="mt-4 flex justify-center space-x-3">
                                <a href="{{ route('deudas.create', ['type' => 'receivable']) }}" class="text-green-600 hover:text-green-800 font-medium">
                                    + Agregar por cobrar
                                </a>
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('deudas.create', ['type' => 'payable']) }}" class="text-red-600 hover:text-red-800 font-medium">
                                    + Agregar por pagar
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($deudas->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $deudas->links() }}
        </div>
        @endif
    </div>

    {{-- Modal de Pago --}}
    <div x-show="modalPago" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="cerrarModalPago()">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                 @click.stop>
                <form :action="'/debts/' + pagoDeudaId + '/pago'" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Registrar Pago</h3>
                            <button type="button" @click="cerrarModalPago()" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <p class="text-sm text-gray-600 mb-4">
                            Registrar pago para: <strong x-text="pagoPersona"></strong>
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto a pagar *</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                    <input type="number" name="monto" step="0.01" :max="pagoMaximo" required x-model="pagoMonto"
                                           class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Máximo: $<span x-text="pagoMaximo.toFixed(2)"></span></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago *</label>
                                <input type="date" name="fecha_pago" required value="{{ date('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago</label>
                                <select name="metodo_pago" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia/Comprobante</label>
                                <input type="text" name="referencia" placeholder="Número de referencia..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                                <textarea name="notas" rows="2" placeholder="Notas adicionales..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-white font-medium hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Registrar Pago
                        </button>
                        <button type="button" @click="cerrarModalPago()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deudasApp() {
    return {
        modalPago: false,
        pagoDeudaId: null,
        pagoPersona: '',
        pagoMaximo: 0,
        pagoMonto: 0,

        abrirModalPago(deudaId, persona, maximo) {
            this.pagoDeudaId = deudaId;
            this.pagoPersona = persona;
            this.pagoMaximo = maximo;
            this.pagoMonto = maximo;
            this.modalPago = true;
        },

        cerrarModalPago() {
            this.modalPago = false;
            this.pagoDeudaId = null;
            this.pagoPersona = '';
            this.pagoMaximo = 0;
            this.pagoMonto = 0;
        }
    }
}
</script>
@endpush
@endsection
