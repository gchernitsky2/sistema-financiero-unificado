<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PagoProgramadoController;
use App\Http\Controllers\ProyeccionController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\MetaFinancieraController;
use App\Http\Controllers\RecordatorioController;
use App\Http\Controllers\ConfirmacionSaldoController;
use App\Http\Controllers\DeudaController;

/*
|--------------------------------------------------------------------------
| Sistema Financiero Unificado - Rutas Web
|--------------------------------------------------------------------------
*/

// Dashboard principal
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// ============================================
// CATÁLOGOS
// ============================================

// Bancos
Route::resource('bancos', BancoController::class);
Route::post('bancos/{banco}/update-field', [BancoController::class, 'updateField'])->name('bancos.update-field');

// Cuentas Bancarias
Route::resource('cuentas', CuentaBancariaController::class);
Route::post('cuentas/{cuenta}/set-principal', [CuentaBancariaController::class, 'setPrincipal'])->name('cuentas.set-principal');
Route::post('cuentas/{cuenta}/recalcular-saldo', [CuentaBancariaController::class, 'recalcularSaldo'])->name('cuentas.recalcular-saldo');

// Categorías
Route::resource('categorias', CategoriaController::class)->except(['show']);

// ============================================
// MOVIMIENTOS BANCARIOS
// ============================================

Route::resource('movimientos', MovimientoController::class);
Route::get('movimientos-multiple/create', [MovimientoController::class, 'createMultiple'])->name('movimientos.create-multiple');
Route::post('movimientos-multiple', [MovimientoController::class, 'storeMultiple'])->name('movimientos.store-multiple');
Route::patch('movimientos/{movimiento}/marcar-pagado', [MovimientoController::class, 'marcarPagado'])->name('movimientos.marcar-pagado');

// ============================================
// PAGOS PROGRAMADOS (con IA)
// ============================================

Route::resource('pagos-programados', PagoProgramadoController::class);
Route::get('pagos-programados-ia', [PagoProgramadoController::class, 'dashboardIA'])->name('pagos-programados.dashboard-ia');
Route::post('pagos-programados-recalcular', [PagoProgramadoController::class, 'recalcularPrioridades'])->name('pagos-programados.recalcular');
Route::patch('pagos-programados/{pagosProgramado}/marcar-pagado', [PagoProgramadoController::class, 'marcarPagado'])->name('pagos-programados.marcar-pagado');
Route::patch('pagos-programados/{pagosProgramado}/cancelar', [PagoProgramadoController::class, 'cancelar'])->name('pagos-programados.cancelar');

// ============================================
// PROYECCIONES DE FLUJO
// ============================================

Route::prefix('proyeccion')->name('proyeccion.')->group(function () {
    Route::get('/', [ProyeccionController::class, 'index'])->name('index');
    Route::get('/escenarios', [ProyeccionController::class, 'escenarios'])->name('escenarios');
    Route::get('/tendencias', [ProyeccionController::class, 'tendencias'])->name('tendencias');
    Route::get('/comparativo', [ProyeccionController::class, 'comparativo'])->name('comparativo');
});

// ============================================
// PRÉSTAMOS
// ============================================

Route::resource('prestamos', PrestamoController::class);
Route::post('prestamos/{prestamo}/pagos/{pago}', [PrestamoController::class, 'registrarPago'])->name('prestamos.registrar-pago');
Route::get('prestamos/{prestamo}/amortizacion', [PrestamoController::class, 'amortizacion'])->name('prestamos.amortizacion');
Route::patch('prestamos/{prestamo}/regenerar-amortizacion', [PrestamoController::class, 'regenerarAmortizacion'])->name('prestamos.regenerar-amortizacion');
Route::patch('prestamos/{prestamo}/liquidar', [PrestamoController::class, 'liquidar'])->name('prestamos.liquidar');
Route::patch('prestamos/{prestamo}/cancelar', [PrestamoController::class, 'cancelar'])->name('prestamos.cancelar');

// ============================================
// METAS FINANCIERAS
// ============================================

Route::resource('metas', MetaFinancieraController::class);
Route::post('metas/{meta}/aportar', [MetaFinancieraController::class, 'registrarAporte'])->name('metas.aportar');
Route::patch('metas/{meta}/estatus', [MetaFinancieraController::class, 'cambiarEstado'])->name('metas.cambiar-estado');

// ============================================
// RECORDATORIOS
// ============================================

Route::resource('recordatorios', RecordatorioController::class)->only(['index', 'create', 'store', 'destroy']);
Route::patch('recordatorios/{recordatorio}/marcar-visto', [RecordatorioController::class, 'marcarVisto'])->name('recordatorios.marcar-visto');
Route::patch('recordatorios/{recordatorio}/descartar', [RecordatorioController::class, 'descartar'])->name('recordatorios.descartar');
Route::post('recordatorios/generar-automaticos', [RecordatorioController::class, 'generarAutomaticos'])->name('recordatorios.generar-automaticos');

// API para widget de recordatorios
Route::get('api/recordatorios/widget', [RecordatorioController::class, 'widget'])->name('api.recordatorios.widget');

// ============================================
// DEUDAS (Por Cobrar y Por Pagar)
// ============================================

Route::prefix('debts')->name('deudas.')->group(function () {
    Route::get('/', [DeudaController::class, 'index'])->name('index');
    Route::get('/create', [DeudaController::class, 'create'])->name('create');
    Route::post('/', [DeudaController::class, 'store'])->name('store');
    Route::get('/{deuda}', [DeudaController::class, 'show'])->name('show');
    Route::get('/{deuda}/edit', [DeudaController::class, 'edit'])->name('edit');
    Route::put('/{deuda}', [DeudaController::class, 'update'])->name('update');
    Route::delete('/{deuda}', [DeudaController::class, 'destroy'])->name('destroy');
    Route::post('/{deuda}/pago', [DeudaController::class, 'registrarPago'])->name('pago');
    Route::patch('/{deuda}/marcar-pagada', [DeudaController::class, 'marcarPagada'])->name('marcar-pagada');
    Route::patch('/{deuda}/cancelar', [DeudaController::class, 'cancelar'])->name('cancelar');
    Route::get('/api/estadisticas', [DeudaController::class, 'estadisticas'])->name('estadisticas');
});

// ============================================
// CONFIRMACIÓN DE SALDOS (Verificación)
// ============================================

Route::prefix('verificacion-saldos')->name('confirmacion-saldo.')->group(function () {
    Route::get('/', [ConfirmacionSaldoController::class, 'index'])->name('index');
    Route::get('/verificar-necesidad', [ConfirmacionSaldoController::class, 'verificarNecesidad'])->name('verificar-necesidad');
    Route::post('/confirmar', [ConfirmacionSaldoController::class, 'confirmarSaldos'])->name('confirmar');
    Route::post('/omitir', [ConfirmacionSaldoController::class, 'omitirHoy'])->name('omitir');
    Route::post('/{confirmacion}/ajustar', [ConfirmacionSaldoController::class, 'ajustarSaldo'])->name('ajustar');
});
