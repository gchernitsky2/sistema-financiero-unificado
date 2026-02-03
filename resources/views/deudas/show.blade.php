@extends('layouts.app')

@section('title', 'Detalle de Deuda')
@section('page-title', 'Detalle de Deuda')

@section('header-actions')
<a href="{{ route('deudas.edit', $deuda) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
    </svg>
    Editar
</a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto" x-data="deudaDetalle()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Información principal --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Cabecera --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $deuda->tipo_color }}-100 text-{{ $deuda->tipo_color }}-800">
                                {{ $deuda->tipo_label }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $deuda->estado_color }}-100 text-{{ $deuda->estado_color }}-800">
                                {{ $deuda->estado_label }}
                            </span>
                        </div>
                        <h2 class="mt-3 text-2xl font-bold text-gray-900">{{ $deuda->persona_nombre }}</h2>
                        <p class="text-gray-600 mt-1">{{ $deuda->descripcion }}</p>
                    </div>
                </div>

                {{-- Contacto --}}
                @if($deuda->persona_telefono || $deuda->persona_email)
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-wrap gap-4">
                    @if($deuda->persona_telefono)
                    <a href="tel:{{ $deuda->persona_telefono }}" class="flex items-center text-gray-600 hover:text-primary-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        {{ $deuda->persona_telefono }}
                    </a>
                    @endif
                    @if($deuda->persona_email)
                    <a href="mailto:{{ $deuda->persona_email }}" class="flex items-center text-gray-600 hover:text-primary-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        {{ $deuda->persona_email }}
                    </a>
                    @endif
                </div>
                @endif
            </div>

            {{-- Historial de pagos --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Historial de Pagos</h3>
                    @if($deuda->estado !== 'pagado' && $deuda->estado !== 'cancelado')
                    <button @click="modalPago = true" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Registrar Pago
                    </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    @if($deuda->pagos->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        No hay pagos registrados
                    </div>
                    @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($deuda->pagos as $pago)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $pago->fecha_pago->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-green-600">
                                    +${{ number_format($pago->monto, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $pago->metodo_pago_label }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $pago->referencia ?? '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            {{-- Notas --}}
            @if($deuda->notas)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Notas</h3>
                <p class="text-gray-600 whitespace-pre-line">{{ $deuda->notas }}</p>
            </div>
            @endif
        </div>

        {{-- Panel lateral --}}
        <div class="space-y-6">
            {{-- Resumen de montos --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Resumen</h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Monto original</span>
                        <span class="text-lg font-semibold text-gray-900">${{ number_format($deuda->monto_original, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total pagado</span>
                        <span class="text-lg font-semibold text-green-600">${{ number_format($deuda->monto_pagado, 2) }}</span>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-900 font-medium">Pendiente</span>
                            <span class="text-xl font-bold {{ $deuda->tipo === 'receivable' ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($deuda->monto_original - $deuda->monto_pagado, 2) }}
                            </span>
                        </div>
                    </div>

                    {{-- Barra de progreso --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progreso</span>
                            <span>{{ $deuda->porcentaje_pagado }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-{{ $deuda->tipo_color }}-500 h-2 rounded-full transition-all duration-300"
                                 style="width: {{ $deuda->porcentaje_pagado }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fechas --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Fechas</h3>

                <div class="space-y-3">
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-gray-600">Creación:</span>
                        <span class="ml-auto font-medium text-gray-900">{{ $deuda->fecha_creacion->format('d/m/Y') }}</span>
                    </div>

                    @if($deuda->fecha_vencimiento)
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 {{ $deuda->esta_vencida ? 'text-red-500' : ($deuda->es_urgente ? 'text-orange-500' : 'text-gray-400') }} mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-600">Vencimiento:</span>
                        <span class="ml-auto font-medium {{ $deuda->esta_vencida ? 'text-red-600' : ($deuda->es_urgente ? 'text-orange-600' : 'text-gray-900') }}">
                            {{ $deuda->fecha_vencimiento->format('d/m/Y') }}
                        </span>
                    </div>
                    @if($deuda->dias_para_vencer !== null && $deuda->estado !== 'pagado')
                    <p class="text-xs {{ $deuda->esta_vencida ? 'text-red-500' : ($deuda->es_urgente ? 'text-orange-500' : 'text-gray-500') }} ml-6">
                        @if($deuda->esta_vencida)
                            Vencida hace {{ abs($deuda->dias_para_vencer) }} días
                        @else
                            {{ $deuda->dias_para_vencer }} días para vencer
                        @endif
                    </p>
                    @endif
                    @endif
                </div>
            </div>

            {{-- Categoría y cuenta --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Clasificación</h3>

                <div class="space-y-3">
                    @if($deuda->categoria)
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span class="text-gray-600">Categoría:</span>
                        <span class="ml-auto font-medium text-gray-900">{{ $deuda->categoria->nombre }}</span>
                    </div>
                    @endif

                    @if($deuda->cuentaBancaria)
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span class="text-gray-600">Cuenta:</span>
                        <span class="ml-auto font-medium text-gray-900">{{ $deuda->cuentaBancaria->nombre }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            @if($deuda->estado !== 'pagado' && $deuda->estado !== 'cancelado')
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Acciones</h3>

                <div class="space-y-2">
                    <form action="{{ route('deudas.marcar-pagada', $deuda) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                            Marcar como Pagada
                        </button>
                    </form>

                    <form action="{{ route('deudas.cancelar', $deuda) }}" method="POST"
                          onsubmit="return confirm('¿Estás seguro de cancelar esta deuda?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 text-sm">
                            Cancelar Deuda
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Volver --}}
            <a href="{{ route('deudas.index', ['type' => $deuda->tipo]) }}"
               class="block text-center text-sm text-primary-600 hover:text-primary-800">
                ← Volver al listado
            </a>
        </div>
    </div>

    {{-- Modal de Pago --}}
    <div x-show="modalPago" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 opacity-75" @click="modalPago = false"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full" @click.stop>
                <form action="{{ route('deudas.pago', $deuda) }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrar Pago</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                    <input type="number" name="monto" step="0.01" min="0.01"
                                           max="{{ $deuda->monto_original - $deuda->monto_pagado }}"
                                           value="{{ $deuda->monto_original - $deuda->monto_pagado }}" required
                                           class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Máximo: ${{ number_format($deuda->monto_original - $deuda->monto_pagado, 2) }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                                <input type="date" name="fecha_pago" value="{{ date('Y-m-d') }}" required
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                                <input type="text" name="referencia" placeholder="Número de comprobante..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3">
                        <button type="button" @click="modalPago = false" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Registrar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deudaDetalle() {
    return {
        modalPago: false
    }
}
</script>
@endpush
@endsection
