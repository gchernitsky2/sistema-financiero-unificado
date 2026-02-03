@extends('layouts.app')

@section('title', 'Verificación de Saldos')
@section('page-title', 'Verificación de Saldos Bancarios')

@section('content')
<div x-data="confirmacionSaldos()">
    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Cuentas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $estadisticas['total_cuentas'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $estadisticas['confirmadas_hoy'] >= $estadisticas['total_cuentas'] ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Confirmadas Hoy</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $estadisticas['confirmadas_hoy'] }} / {{ $estadisticas['total_cuentas'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $estadisticas['con_diferencias_pendientes'] > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Con Diferencias</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $estadisticas['con_diferencias_pendientes'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $estadisticas['omitido_hoy'] ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Estado Hoy</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($estadisticas['omitido_hoy'])
                            Omitido
                        @elseif($estadisticas['confirmadas_hoy'] >= $estadisticas['total_cuentas'])
                            Completado
                        @else
                            Pendiente
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Botón de confirmar saldos --}}
    @if($estadisticas['confirmadas_hoy'] < $estadisticas['total_cuentas'] && !$estadisticas['omitido_hoy'])
    <div class="mb-6">
        <button @click="abrirModalConfirmacion()" class="w-full md:w-auto px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Confirmar Saldos del Día
        </button>
    </div>
    @endif

    {{-- Cuentas y estado actual --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Estado de Cuentas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Sistema</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Hoy</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Última Confirmación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cuentas as $cuenta)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    @if($cuenta->tipo === 'banco')
                                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    @elseif($cuenta->tipo === 'efectivo')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">{{ $cuenta->nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ $cuenta->banco?->nombre ?? $cuenta->tipo_label }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-lg font-semibold {{ $cuenta->saldo_actual >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                                ${{ number_format($cuenta->saldo_actual, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($estadoConfirmaciones[$cuenta->id]['confirmada'])
                                @php $conf = $estadoConfirmaciones[$cuenta->id]['confirmacion']; @endphp
                                @if($conf->estado === 'confirmado')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Confirmado
                                    </span>
                                @elseif($conf->estado === 'con_diferencia')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Diferencia: ${{ number_format($conf->diferencia, 2) }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                            @if($estadoConfirmaciones[$cuenta->id]['confirmada'])
                                {{ $estadoConfirmaciones[$cuenta->id]['confirmacion']->created_at->format('H:i') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            No hay cuentas registradas. <a href="{{ route('cuentas.create') }}" class="text-primary-600 hover:text-primary-800">Crear una cuenta</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historial de confirmaciones --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Historial de Confirmaciones</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Sistema</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Real</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Diferencia</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($historialConfirmaciones as $confirmacion)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $confirmacion->fecha_confirmacion->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $confirmacion->cuentaBancaria->nombre_completo ?? 'Cuenta eliminada' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            ${{ number_format($confirmacion->saldo_sistema, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            ${{ number_format($confirmacion->saldo_real, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm {{ $confirmacion->diferencia == 0 ? 'text-gray-500' : ($confirmacion->diferencia > 0 ? 'text-green-600' : 'text-red-600') }}">
                            {{ $confirmacion->diferencia >= 0 ? '+' : '' }}${{ number_format($confirmacion->diferencia, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $confirmacion->estado_color }}-100 text-{{ $confirmacion->estado_color }}-800">
                                {{ $confirmacion->estado_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No hay confirmaciones registradas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal de Confirmación de Saldos --}}
    <div x-show="modalAbierto" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div class="fixed inset-0 transition-opacity" @click="cerrarModal()">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            {{-- Modal Content --}}
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                 @click.stop>
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Saldos del Día</h3>
                        <button @click="cerrarModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 mb-4">
                        Ingresa el saldo real de cada cuenta según tu estado de cuenta bancario o efectivo disponible.
                    </p>

                    {{-- Lista de cuentas para confirmar --}}
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach($cuentas as $index => $cuenta)
                            @if(!$estadoConfirmaciones[$cuenta->id]['confirmada'])
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $cuenta->nombre }}</p>
                                        <p class="text-xs text-gray-500">{{ $cuenta->banco?->nombre ?? $cuenta->tipo_label }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Saldo en sistema</p>
                                        <p class="font-semibold {{ $cuenta->saldo_actual >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                                            ${{ number_format($cuenta->saldo_actual, 2) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Real</label>
                                    <div class="flex">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                        <input type="number"
                                               step="0.01"
                                               x-model="confirmaciones[{{ $cuenta->id }}].saldo_real"
                                               class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                               placeholder="{{ number_format($cuenta->saldo_actual, 2) }}">
                                    </div>
                                    <p class="mt-1 text-xs"
                                       :class="getDiferenciaClass({{ $cuenta->id }}, {{ $cuenta->saldo_actual }})">
                                        <span x-text="getDiferenciaTexto({{ $cuenta->id }}, {{ $cuenta->saldo_actual }})"></span>
                                    </p>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Error message --}}
                    <div x-show="error" x-cloak class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <p x-text="error"></p>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                            @click="confirmarSaldos()"
                            :disabled="procesando"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!procesando">Confirmar Saldos</span>
                        <span x-show="procesando" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </button>
                    <button type="button"
                            @click="omitirHoy()"
                            :disabled="procesando"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Omitir por Hoy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmacionSaldos() {
    return {
        modalAbierto: false,
        procesando: false,
        error: null,
        confirmaciones: {
            @foreach($cuentas as $cuenta)
                @if(!$estadoConfirmaciones[$cuenta->id]['confirmada'])
                {{ $cuenta->id }}: {
                    cuenta_id: {{ $cuenta->id }},
                    saldo_real: '',
                    notas: ''
                },
                @endif
            @endforeach
        },

        abrirModalConfirmacion() {
            this.modalAbierto = true;
            this.error = null;
        },

        cerrarModal() {
            this.modalAbierto = false;
            this.error = null;
        },

        getDiferenciaClass(cuentaId, saldoSistema) {
            const saldoReal = parseFloat(this.confirmaciones[cuentaId]?.saldo_real || 0);
            if (!saldoReal) return 'text-gray-400';

            const diferencia = saldoReal - saldoSistema;
            if (Math.abs(diferencia) < 0.01) return 'text-green-600';
            return diferencia > 0 ? 'text-blue-600' : 'text-red-600';
        },

        getDiferenciaTexto(cuentaId, saldoSistema) {
            const saldoReal = parseFloat(this.confirmaciones[cuentaId]?.saldo_real || 0);
            if (!saldoReal) return 'Ingresa el saldo real';

            const diferencia = saldoReal - saldoSistema;
            if (Math.abs(diferencia) < 0.01) return 'Los saldos coinciden';

            const signo = diferencia > 0 ? '+' : '';
            return `Diferencia: ${signo}$${diferencia.toFixed(2)}`;
        },

        async confirmarSaldos() {
            this.procesando = true;
            this.error = null;

            // Preparar datos
            const confirmacionesArray = [];
            for (const [cuentaId, data] of Object.entries(this.confirmaciones)) {
                if (data.saldo_real === '' || data.saldo_real === null) {
                    this.error = 'Por favor ingresa el saldo real de todas las cuentas';
                    this.procesando = false;
                    return;
                }
                confirmacionesArray.push({
                    cuenta_id: parseInt(cuentaId),
                    saldo_real: parseFloat(data.saldo_real),
                    notas: data.notas || null
                });
            }

            try {
                const response = await fetch('{{ route("confirmacion-saldo.confirmar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ confirmaciones: confirmacionesArray })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    this.error = data.message || 'Error al confirmar saldos';
                }
            } catch (e) {
                this.error = 'Error de conexión. Por favor intenta de nuevo.';
                console.error('Error:', e);
            } finally {
                this.procesando = false;
            }
        },

        async omitirHoy() {
            this.procesando = true;
            this.error = null;

            try {
                const response = await fetch('{{ route("confirmacion-saldo.omitir") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    this.error = data.message || 'Error al omitir';
                }
            } catch (e) {
                this.error = 'Error de conexión. Por favor intenta de nuevo.';
                console.error('Error:', e);
            } finally {
                this.procesando = false;
            }
        }
    }
}
</script>
@endpush
@endsection
