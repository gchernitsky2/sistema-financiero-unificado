@extends('layouts.app')

@section('title', 'Metas Financieras')
@section('page-title', 'Metas Financieras')

@section('header-actions')
    <a href="{{ route('metas.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nueva Meta
    </a>
@endsection

@section('content')
<!-- Resumen -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg shadow p-4 text-white">
        <p class="text-yellow-100 text-sm">Metas Activas</p>
        <p class="text-3xl font-bold">{{ $contadores['activas'] ?? 0 }}</p>
    </div>
    <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg shadow p-4 text-white">
        <p class="text-green-100 text-sm">Completadas</p>
        <p class="text-3xl font-bold">{{ $contadores['completadas'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Total Ahorrado</p>
        <p class="text-2xl font-bold text-primary-600">${{ number_format($contadores['total_ahorrado'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Por Ahorrar</p>
        <p class="text-2xl font-bold text-gray-800">${{ number_format($contadores['total_restante'] ?? 0, 2) }}</p>
    </div>
</div>

<!-- Lista de Metas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($metas ?? [] as $meta)
        @php
            $progreso = $meta->monto_objetivo > 0 ? ($meta->monto_actual / $meta->monto_objetivo) * 100 : 0;
            $progreso = min($progreso, 100);
        @endphp
        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow overflow-hidden">
            <!-- Barra de progreso superior -->
            <div class="h-2 bg-gray-200">
                <div class="h-full transition-all duration-500 {{ $progreso >= 100 ? 'bg-green-500' : ($progreso >= 50 ? 'bg-yellow-500' : 'bg-primary-500') }}"
                     style="width: {{ $progreso }}%"></div>
            </div>

            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $meta->nombre }}</h3>
                        @if($meta->categoria)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 mt-1">
                                {{ $meta->categoria }}
                            </span>
                        @endif
                    </div>
                    @if($meta->estado == 'completada')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Completada
                        </span>
                    @elseif($meta->estado == 'pausada')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Pausada
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Activa
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-sm text-gray-500">Ahorrado</p>
                            <p class="text-xl font-bold text-primary-600">${{ number_format($meta->monto_actual, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Objetivo</p>
                            <p class="text-xl font-bold text-gray-900">${{ number_format($meta->monto_objetivo, 2) }}</p>
                        </div>
                    </div>

                    <div class="text-center">
                        <span class="text-2xl font-bold {{ $progreso >= 100 ? 'text-green-600' : 'text-gray-700' }}">
                            {{ number_format($progreso, 1) }}%
                        </span>
                    </div>

                    @if($meta->fecha_objetivo)
                        <div class="text-center text-sm text-gray-500">
                            Meta: {{ $meta->fecha_objetivo->format('d/m/Y') }}
                            @php
                                $diasRestantes = now()->diffInDays($meta->fecha_objetivo, false);
                            @endphp
                            @if($diasRestantes > 0)
                                <span class="text-gray-400">({{ $diasRestantes }} días)</span>
                            @elseif($diasRestantes < 0 && $meta->estado != 'completada')
                                <span class="text-red-500">(Vencida)</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between">
                    @if($meta->estado == 'activa')
                        <button type="button"
                                onclick="abrirModalAporte({{ $meta->id }}, '{{ $meta->nombre }}')"
                                class="inline-flex items-center text-sm text-green-600 hover:text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Aportar
                        </button>
                    @else
                        <span></span>
                    @endif
                    <div class="flex space-x-2">
                        <a href="{{ route('metas.show', $meta) }}" class="text-gray-600 hover:text-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        <a href="{{ route('metas.edit', $meta) }}" class="text-primary-600 hover:text-primary-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                <p class="mt-4 text-gray-500">No hay metas financieras</p>
                <a href="{{ route('metas.create') }}" class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-700">
                    Crear primera meta
                </a>
            </div>
        </div>
    @endforelse
</div>

<!-- Modal de Aporte -->
<div x-data="{ open: false, metaId: null, metaNombre: '' }"
     x-show="open"
     x-cloak
     @abrir-modal-aporte.window="open = true; metaId = $event.detail.id; metaNombre = $event.detail.nombre"
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" @click="open = false"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Aporte</h3>
            <p class="text-sm text-gray-500 mb-4">Meta: <span x-text="metaNombre" class="font-medium"></span></p>

            <form :action="'/metas/' + metaId + '/aportar'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto del Aporte</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto" step="0.01" min="0.01" required
                               class="w-full pl-8 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                    <input type="text" name="notas" class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Registrar Aporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirModalAporte(id, nombre) {
    window.dispatchEvent(new CustomEvent('abrir-modal-aporte', { detail: { id, nombre } }));
}
</script>
@endpush
@endsection
