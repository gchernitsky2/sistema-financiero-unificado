@extends('layouts.app')

@section('title', 'Amortización')
@section('page-title', 'Tabla de Amortización')

@section('header-actions')
    <a href="{{ route('prestamos.show', $prestamo) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Volver al Préstamo
    </a>
@endsection

@section('content')
<!-- Resumen del Préstamo -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-sm text-gray-500">Descripción</p>
            <p class="text-lg font-semibold text-gray-900">{{ $prestamo->descripcion }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Monto Total</p>
            <p class="text-lg font-semibold text-gray-900">${{ number_format($prestamo->monto_total, 2) }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Saldo Pendiente</p>
            <p class="text-lg font-semibold {{ $prestamo->saldo_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                ${{ number_format($prestamo->saldo_pendiente, 2) }}
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Tasa de Interés</p>
            <p class="text-lg font-semibold text-gray-900">{{ $prestamo->tasa_interes }}% {{ $prestamo->tipo_interes }}</p>
        </div>
    </div>
</div>

<!-- Tabla de Amortización -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">Tabla de Pagos</h3>
        @if($prestamo->estado == 'activo')
            <form action="{{ route('prestamos.regenerar-amortizacion', $prestamo) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="text-sm text-primary-600 hover:text-primary-800">
                    Regenerar tabla
                </button>
            </form>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pago</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Capital</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Interés</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($prestamo->pagos ?? [] as $pago)
                    <tr class="hover:bg-gray-50 {{ $pago->pagado ? 'bg-green-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $pago->numero_pago }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $pago->fecha_vencimiento->format('d/m/Y') }}
                            @if($pago->fecha_pago)
                                <span class="block text-xs text-green-600">Pagado: {{ $pago->fecha_pago->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                            ${{ number_format($pago->monto_pago, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            ${{ number_format($pago->capital, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            ${{ number_format($pago->interes, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                            ${{ number_format($pago->saldo_restante, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($pago->pagado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Pagado
                                </span>
                            @elseif($pago->fecha_vencimiento < now())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Vencido
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if(!$pago->pagado && $prestamo->estado == 'activo')
                                <form action="{{ route('prestamos.registrar-pago', [$prestamo, $pago]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Registrar pago">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No hay pagos programados
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($prestamo->pagos && $prestamo->pagos->count() > 0)
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Totales:</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                            ${{ number_format($prestamo->pagos->sum('monto_pago'), 2) }}
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                            ${{ number_format($prestamo->pagos->sum('capital'), 2) }}
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                            ${{ number_format($prestamo->pagos->sum('interes'), 2) }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
