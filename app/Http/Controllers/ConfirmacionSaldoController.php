<?php

namespace App\Http\Controllers;

use App\Models\ConfirmacionSaldo;
use App\Models\OmisionConfirmacion;
use App\Models\CuentaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfirmacionSaldoController extends Controller
{
    /**
     * Vista principal de verificación de saldos
     */
    public function index()
    {
        $cuentas = CuentaBancaria::with('banco')
            ->activas()
            ->orderBy('es_principal', 'desc')
            ->orderBy('nombre')
            ->get();

        // Verificar el estado de confirmación de hoy para cada cuenta
        $estadoConfirmaciones = [];
        foreach ($cuentas as $cuenta) {
            $confirmacionHoy = ConfirmacionSaldo::where('cuenta_bancaria_id', $cuenta->id)
                ->whereDate('fecha_confirmacion', today())
                ->first();

            $estadoConfirmaciones[$cuenta->id] = [
                'confirmada' => $confirmacionHoy !== null,
                'confirmacion' => $confirmacionHoy,
            ];
        }

        // Historial de confirmaciones recientes
        $historialConfirmaciones = ConfirmacionSaldo::with('cuentaBancaria.banco')
            ->orderByDesc('fecha_confirmacion')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Estadísticas
        $estadisticas = [
            'confirmadas_hoy' => ConfirmacionSaldo::whereDate('fecha_confirmacion', today())->count(),
            'total_cuentas' => $cuentas->count(),
            'con_diferencias_pendientes' => ConfirmacionSaldo::where('estado', 'con_diferencia')
                ->orWhere('estado', 'pendiente_ajuste')
                ->count(),
            'omitido_hoy' => OmisionConfirmacion::where('fecha', today())->exists(),
        ];

        return view('confirmacion-saldo.index', compact(
            'cuentas',
            'estadoConfirmaciones',
            'historialConfirmaciones',
            'estadisticas'
        ));
    }

    /**
     * Verificar si se necesita mostrar el modal de confirmación
     */
    public function verificarNecesidad()
    {
        $cuentas = CuentaBancaria::activas()->count();

        if ($cuentas === 0) {
            return response()->json([
                'mostrar_modal' => false,
                'razon' => 'no_cuentas',
            ]);
        }

        // Si ya se omitió hoy, no mostrar
        if (OmisionConfirmacion::where('fecha', today())->exists()) {
            return response()->json([
                'mostrar_modal' => false,
                'razon' => 'omitido',
            ]);
        }

        // Verificar si todas las cuentas ya fueron confirmadas hoy
        $confirmacionesHoy = ConfirmacionSaldo::whereDate('fecha_confirmacion', today())
            ->distinct('cuenta_bancaria_id')
            ->count('cuenta_bancaria_id');

        if ($confirmacionesHoy >= $cuentas) {
            return response()->json([
                'mostrar_modal' => false,
                'razon' => 'todas_confirmadas',
            ]);
        }

        // Obtener cuentas pendientes de confirmar
        $cuentasPendientes = CuentaBancaria::with('banco')
            ->activas()
            ->whereNotIn('id', function ($query) {
                $query->select('cuenta_bancaria_id')
                    ->from('confirmaciones_saldo')
                    ->whereDate('fecha_confirmacion', today());
            })
            ->get();

        return response()->json([
            'mostrar_modal' => true,
            'cuentas_pendientes' => $cuentasPendientes->count(),
            'cuentas' => $cuentasPendientes->map(function ($cuenta) {
                return [
                    'id' => $cuenta->id,
                    'nombre' => $cuenta->nombre_completo,
                    'saldo_sistema' => $cuenta->saldo_actual,
                    'tipo' => $cuenta->tipo_label,
                ];
            }),
        ]);
    }

    /**
     * Confirmar saldos de todas las cuentas
     */
    public function confirmarSaldos(Request $request)
    {
        $request->validate([
            'confirmaciones' => 'required|array',
            'confirmaciones.*.cuenta_id' => 'required|exists:cuentas_bancarias,id',
            'confirmaciones.*.saldo_real' => 'required|numeric',
            'confirmaciones.*.notas' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $resultados = [];
            $hayDiferencias = false;

            foreach ($request->confirmaciones as $confirmacion) {
                $cuenta = CuentaBancaria::findOrFail($confirmacion['cuenta_id']);

                // No confirmar si ya fue confirmada hoy
                if (ConfirmacionSaldo::yaConfirmadoHoy($cuenta->id)) {
                    continue;
                }

                $conf = ConfirmacionSaldo::confirmarSaldo(
                    $cuenta,
                    $confirmacion['saldo_real'],
                    $confirmacion['notas'] ?? null
                );

                if ($conf->estado === 'con_diferencia') {
                    $hayDiferencias = true;
                }

                $resultados[] = [
                    'cuenta_id' => $cuenta->id,
                    'cuenta_nombre' => $cuenta->nombre_completo,
                    'saldo_sistema' => $conf->saldo_sistema,
                    'saldo_real' => $conf->saldo_real,
                    'diferencia' => $conf->diferencia,
                    'estado' => $conf->estado,
                ];
            }

            DB::commit();

            $mensaje = $hayDiferencias
                ? 'Saldos confirmados. Se detectaron diferencias que requieren revisión.'
                : 'Todos los saldos fueron confirmados exitosamente.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'hay_diferencias' => $hayDiferencias,
                    'resultados' => $resultados,
                ]);
            }

            return redirect()->route('confirmacion-saldo.index')
                ->with($hayDiferencias ? 'warning' : 'success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al confirmar saldos: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al confirmar saldos: ' . $e->getMessage());
        }
    }

    /**
     * Omitir la confirmación de saldos por hoy
     */
    public function omitirHoy(Request $request)
    {
        $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        try {
            OmisionConfirmacion::omitirHoy($request->motivo);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Confirmación omitida por hoy. Se te recordará mañana.',
                ]);
            }

            return redirect()->route('dashboard')
                ->with('info', 'Confirmación omitida por hoy. Se te recordará mañana.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al omitir: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al omitir: ' . $e->getMessage());
        }
    }

    /**
     * Ajustar el saldo del sistema al saldo real
     */
    public function ajustarSaldo(Request $request, ConfirmacionSaldo $confirmacion)
    {
        $request->validate([
            'crear_movimiento' => 'boolean',
            'descripcion_movimiento' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $cuenta = $confirmacion->cuentaBancaria;
            $diferencia = $confirmacion->diferencia;

            if ($request->crear_movimiento && abs($diferencia) > 0.01) {
                // Crear un movimiento de ajuste
                $cuenta->movimientos()->create([
                    'tipo' => $diferencia > 0 ? 'ingreso' : 'egreso',
                    'monto' => abs($diferencia),
                    'descripcion' => $request->descripcion_movimiento ?? 'Ajuste por diferencia de saldo',
                    'fecha' => today(),
                    'estado' => 'completado',
                    'es_ajuste' => true,
                ]);

                // Recalcular saldo de la cuenta
                $cuenta->recalcularSaldo();
            } else {
                // Ajustar directamente el saldo
                $cuenta->saldo_actual = $confirmacion->saldo_real;
                $cuenta->save();
            }

            $confirmacion->estado = 'ajustado';
            $confirmacion->save();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Saldo ajustado correctamente.',
                    'nuevo_saldo' => $cuenta->fresh()->saldo_actual,
                ]);
            }

            return redirect()->route('confirmacion-saldo.index')
                ->with('success', 'Saldo ajustado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al ajustar saldo: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al ajustar saldo: ' . $e->getMessage());
        }
    }
}
