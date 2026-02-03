@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Saldo Actual -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Saldo Actual</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($resumenMes['saldo_actual'], 2) }}</p>
                </div>
                <div class="p-3 bg-primary-100 rounded-full">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Ingresos del Mes -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ingresos del Mes</p>
                    <p class="text-2xl font-bold text-green-600">+${{ number_format($resumenMes['ingresos'], 2) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Egresos del Mes -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Egresos del Mes</p>
                    <p class="text-2xl font-bold text-red-600">-${{ number_format($resumenMes['egresos'], 2) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Flujo Neto -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Flujo Neto</p>
                    <p class="text-2xl font-bold {{ $resumenMes['flujo_neto'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $resumenMes['flujo_neto'] >= 0 ? '+' : '' }}${{ number_format($resumenMes['flujo_neto'], 2) }}
                    </p>
                </div>
                <div class="p-3 {{ $resumenMes['flujo_neto'] >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-full">
                    <svg class="w-6 h-6 {{ $resumenMes['flujo_neto'] >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicadores de Salud Financiera -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Salud Financiera</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 rounded-lg {{ $saludFinanciera['estado'] === 'buena' ? 'bg-green-50' : ($saludFinanciera['estado'] === 'regular' ? 'bg-yellow-50' : 'bg-red-50') }}">
                <p class="text-sm text-gray-500">Estado</p>
                <p class="text-xl font-bold {{ $saludFinanciera['estado'] === 'buena' ? 'text-green-600' : ($saludFinanciera['estado'] === 'regular' ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ ucfirst($saludFinanciera['estado']) }}
                </p>
            </div>
            <div class="text-center p-4 rounded-lg bg-blue-50">
                <p class="text-sm text-gray-500">Meses de Cobertura</p>
                <p class="text-xl font-bold text-blue-600">{{ $saludFinanciera['meses_cobertura'] }}</p>
            </div>
            <div class="text-center p-4 rounded-lg bg-purple-50">
                <p class="text-sm text-gray-500">Volatilidad</p>
                <p class="text-xl font-bold text-purple-600">{{ $saludFinanciera['volatilidad'] }}%</p>
            </div>
        </div>
        @if(count($saludFinanciera['recomendaciones']) > 0)
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700 mb-2">Recomendaciones:</p>
                <ul class="text-sm text-gray-600 space-y-1">
                    @foreach($saludFinanciera['recomendaciones'] as $recomendacion)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-primary-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $recomendacion }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gráfica de Flujo -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Flujo de Efectivo (Real vs Proyectado)</h3>
            <canvas id="flujoChart" height="250"></canvas>
        </div>

        <!-- Saldo por Cuenta -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Saldo por Cuenta</h3>
            <div class="space-y-3">
                @forelse($saldosPorCuenta as $cuenta)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $cuenta['color'] ?? '#6B7280' }}"></div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $cuenta['nombre'] }}</p>
                                <p class="text-xs text-gray-500">{{ $cuenta['tipo'] }}</p>
                            </div>
                        </div>
                        <span class="font-semibold {{ $cuenta['saldo'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($cuenta['saldo'], 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No hay cuentas configuradas</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pagos Urgentes -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Pagos Urgentes</h3>
                <a href="{{ route('pagos-programados.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Ver todos</a>
            </div>
            <div class="space-y-3">
                @forelse($pagosUrgentes as $pago)
                    <div class="flex items-center justify-between p-3 rounded-lg {{ $pago->estado === 'vencido' ? 'bg-red-50' : ($pago->color_semaforo === 'orange' ? 'bg-orange-50' : 'bg-gray-50') }}">
                        <div>
                            <p class="font-medium text-gray-900">{{ $pago->concepto }}</p>
                            <p class="text-xs text-gray-500">{{ $pago->fecha_programada->format('d/m/Y') }} - {{ $pago->beneficiario }}</p>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-red-600">${{ number_format($pago->monto, 2) }}</span>
                            @if($pago->estado === 'vencido')
                                <span class="block text-xs text-red-600 font-medium">VENCIDO</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No hay pagos urgentes</p>
                @endforelse
            </div>
        </div>

        <!-- Últimos Movimientos -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Últimos Movimientos</h3>
                <a href="{{ route('movimientos.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Ver todos</a>
            </div>
            <div class="space-y-3">
                @forelse($ultimosMovimientos as $movimiento)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ Str::limit($movimiento->concepto, 35) }}</p>
                            <p class="text-xs text-gray-500">{{ $movimiento->fecha->format('d/m/Y') }} - {{ $movimiento->cuentaBancaria?->nombre }}</p>
                        </div>
                        <span class="font-semibold {{ $movimiento->tipo === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $movimiento->tipo === 'ingreso' ? '+' : '-' }}${{ number_format($movimiento->monto, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No hay movimientos registrados</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recordatorios -->
    @if($recordatorios->count() > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Recordatorios Pendientes</h3>
            <a href="{{ route('recordatorios.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Ver todos</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($recordatorios as $recordatorio)
                <div class="p-4 rounded-lg {{ $recordatorio->es_vencido ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium {{ $recordatorio->es_vencido ? 'text-red-700' : 'text-yellow-700' }}">{{ $recordatorio->titulo }}</p>
                            <p class="text-xs {{ $recordatorio->es_vencido ? 'text-red-600' : 'text-yellow-600' }}">{{ $recordatorio->fecha_recordatorio->format('d/m/Y') }}</p>
                        </div>
                        <form action="{{ route('recordatorios.marcar-visto', $recordatorio) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-primary-600">{{ $estadisticas['total_cuentas'] }}</p>
            <p class="text-sm text-gray-500">Cuentas Activas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $estadisticas['pagos_vencidos'] }}</p>
            <p class="text-sm text-gray-500">Pagos Vencidos</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $estadisticas['prestamos_activos'] }}</p>
            <p class="text-sm text-gray-500">Préstamos Activos</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $estadisticas['metas_activas'] }}</p>
            <p class="text-sm text-gray-500">Metas Activas</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('flujoChart').getContext('2d');
    const datosGrafica = @json($datosGrafica);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: datosGrafica.map(d => d.fecha),
            datasets: [
                {
                    label: 'Ingresos Reales',
                    data: datosGrafica.map(d => d.ingresos_real),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.3,
                    fill: false,
                },
                {
                    label: 'Egresos Reales',
                    data: datosGrafica.map(d => d.egresos_real),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3,
                    fill: false,
                },
                {
                    label: 'Ingresos Proyectados',
                    data: datosGrafica.map(d => d.ingresos_proyectado),
                    borderColor: 'rgb(34, 197, 94)',
                    borderDash: [5, 5],
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    fill: false,
                },
                {
                    label: 'Egresos Proyectados',
                    data: datosGrafica.map(d => d.egresos_proyectado),
                    borderColor: 'rgb(239, 68, 68)',
                    borderDash: [5, 5],
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
