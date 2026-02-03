@extends('layouts.app')

@section('title', 'Cuentas Bancarias')
@section('page-title', 'Cuentas Bancarias')

@section('header-actions')
    <a href="{{ route('cuentas.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nueva Cuenta
    </a>
@endsection

@section('content')
<!-- Resumen Total -->
<div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-primary-100 text-sm">Saldo Total</p>
            <p class="text-3xl font-bold">${{ number_format($saldoTotal ?? 0, 2) }}</p>
        </div>
        <div class="text-right">
            <p class="text-primary-100 text-sm">Cuentas Activas</p>
            <p class="text-3xl font-bold">{{ $cuentas->count() }}</p>
        </div>
    </div>
</div>

<!-- Lista de Cuentas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($cuentas as $cuenta)
        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $cuenta->es_principal ? 'bg-yellow-100' : 'bg-gray-100' }}">
                            @if($cuenta->tipo == 'banco')
                                <svg class="w-6 h-6 {{ $cuenta->es_principal ? 'text-yellow-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            @elseif($cuenta->tipo == 'efectivo')
                                <svg class="w-6 h-6 {{ $cuenta->es_principal ? 'text-yellow-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            @elseif($cuenta->tipo == 'tarjeta')
                                <svg class="w-6 h-6 {{ $cuenta->es_principal ? 'text-yellow-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            @else
                                <svg class="w-6 h-6 {{ $cuenta->es_principal ? 'text-yellow-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">{{ $cuenta->nombre }}</h3>
                            <p class="text-sm text-gray-500">{{ $cuenta->banco->nombre ?? 'Sin banco' }}</p>
                        </div>
                    </div>
                    @if($cuenta->es_principal)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Principal
                        </span>
                    @endif
                </div>

                <div class="mt-4">
                    <p class="text-2xl font-bold {{ $cuenta->saldo_actual >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                        ${{ number_format($cuenta->saldo_actual, 2) }}
                    </p>
                    @if($cuenta->numero_cuenta)
                        <p class="text-sm text-gray-500 mt-1">
                            ****{{ substr($cuenta->numero_cuenta, -4) }}
                        </p>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cuenta->activa ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $cuenta->activa ? 'Activa' : 'Inactiva' }}
                    </span>
                    <div class="flex space-x-2">
                        @if(!$cuenta->es_principal)
                            <form action="{{ route('cuentas.set-principal', $cuenta) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="Establecer como principal">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('cuentas.edit', $cuenta) }}" class="text-primary-600 hover:text-primary-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('cuentas.recalcular-saldo', $cuenta) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-800" title="Recalcular saldo">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <p class="mt-4 text-gray-500">No hay cuentas registradas</p>
                <a href="{{ route('cuentas.create') }}" class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-700">
                    Crear primera cuenta
                </a>
            </div>
        </div>
    @endforelse
</div>
@endsection
