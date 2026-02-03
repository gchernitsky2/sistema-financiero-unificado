@extends('layouts.app')

@section('title', 'Proyección')
@section('page-title', 'Proyección de Flujo de Efectivo')

@section('header-actions')
    <div class="flex space-x-2">
        <a href="{{ route('proyeccion.escenarios') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Escenarios
        </a>
        <a href="{{ route('proyeccion.tendencias') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
            Tendencias
        </a>
    </div>
@endsection

@php
    // Extraer datos de la estructura del servicio
    $saldoActual = $proyeccion['saldo_inicial'] ?? 0;
    $ingresosProyectados = $proyeccion['total_ingresos'] ?? 0;
    $egresosProyectados = $proyeccion['total_egresos'] ?? 0;
    $proyeccionDias = $proyeccion['proyeccion'] ?? [];
@endphp

@section('content')
<!-- Saldo Proyectado -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-lg shadow-lg p-6 text-white">
        <p class="text-primary-100 text-sm">Saldo Actual</p>
        <p class="text-3xl font-bold">${{ number_format($saldoActual, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Ingresos Proyectados ({{ $dias }} días)</p>
        <p class="text-2xl font-bold text-green-600">${{ number_format($ingresosProyectados, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Egresos Proyectados ({{ $dias }} días)</p>
        <p class="text-2xl font-bold text-red-600">${{ number_format($egresosProyectados, 2) }}</p>
    </div>
</div>

<!-- Gráfica de Proyección -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Proyección de Saldo (30 días)</h3>
    <div class="h-80">
        <canvas id="proyeccionChart"></canvas>
    </div>
</div>

<!-- Tabla de Proyección Diaria -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Detalle de Proyección</h3>
    </div>
    <div class="overflow-x-auto max-h-96">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Egresos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Flujo Neto</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($proyeccionDias as $dia)
                    <tr class="hover:bg-gray-50 {{ ($dia['saldo_proyectado'] ?? 0) < 0 ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}
                            <span class="text-xs text-gray-500">({{ $dia['dia_semana'] ?? \Carbon\Carbon::parse($dia['fecha'])->locale('es')->dayName }})</span>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-green-600">
                            {{ ($dia['ingresos'] ?? 0) > 0 ? '$' . number_format($dia['ingresos'], 2) : '-' }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-red-600">
                            {{ ($dia['egresos'] ?? 0) > 0 ? '$' . number_format($dia['egresos'], 2) : '-' }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium {{ ($dia['flujo_neto'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($dia['flujo_neto'] ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-bold {{ ($dia['saldo_proyectado'] ?? 0) >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                            ${{ number_format($dia['saldo_proyectado'] ?? 0, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No hay datos de proyección disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('proyeccionChart').getContext('2d');
    const proyeccionData = @json($proyeccionDias);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: proyeccionData.map(d => d.fecha),
            datasets: [{
                label: 'Saldo Proyectado',
                data: proyeccionData.map(d => d.saldo_proyectado),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
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
@endsection
