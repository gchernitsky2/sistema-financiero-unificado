<?php

namespace App\Http\Controllers;

use App\Models\Recordatorio;
use Illuminate\Http\Request;

class RecordatorioController extends Controller
{
    public function index(Request $request)
    {
        $query = Recordatorio::query();

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $recordatorios = $query->orderBy('fecha_recordatorio')
            ->orderBy('hora_recordatorio')
            ->paginate(20);

        // Contadores
        $contadores = [
            'pendientes' => Recordatorio::pendientes()->count(),
            'vencidos' => Recordatorio::vencidos()->count(),
            'hoy' => Recordatorio::deHoy()->count(),
        ];

        return view('recordatorios.index', compact('recordatorios', 'contadores'));
    }

    public function create()
    {
        return view('recordatorios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:pago,prestamo,movimiento,meta,general',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'nullable|string|max:1000',
            'fecha_recordatorio' => 'required|date',
            'hora_recordatorio' => 'nullable|date_format:H:i',
            'dias_anticipacion' => 'required|integer|min:0|max:30',
            'repetir' => 'boolean',
            'frecuencia_repeticion' => 'nullable|in:diario,semanal,mensual,anual',
            'notificar_email' => 'boolean',
            'notificar_sistema' => 'boolean',
        ]);

        $validated['repetir'] = $request->boolean('repetir', false);
        $validated['notificar_email'] = $request->boolean('notificar_email', false);
        $validated['notificar_sistema'] = $request->boolean('notificar_sistema', true);
        $validated['estado'] = 'pendiente';
        $validated['user_id'] = auth()->id();

        Recordatorio::create($validated);

        return redirect()->route('recordatorios.index')
            ->with('success', 'Recordatorio creado exitosamente.');
    }

    public function destroy(Recordatorio $recordatorio)
    {
        $recordatorio->delete();

        return redirect()->route('recordatorios.index')
            ->with('success', 'Recordatorio eliminado exitosamente.');
    }

    /**
     * Marcar recordatorio como visto
     */
    public function marcarVisto(Recordatorio $recordatorio)
    {
        $recordatorio->marcarVisto();

        return back()->with('success', 'Recordatorio marcado como visto.');
    }

    /**
     * Descartar recordatorio
     */
    public function descartar(Recordatorio $recordatorio)
    {
        $recordatorio->descartar();

        return back()->with('success', 'Recordatorio descartado.');
    }

    /**
     * Generar recordatorios automáticos
     */
    public function generarAutomaticos()
    {
        $creados = Recordatorio::generarAutomaticos();

        return back()->with('success', "Se crearon {$creados} recordatorio(s) automático(s).");
    }

    /**
     * Widget API para dashboard
     */
    public function widget()
    {
        $recordatorios = Recordatorio::pendientes()
            ->orderBy('fecha_recordatorio')
            ->limit(5)
            ->get();

        $contadores = [
            'pendientes' => Recordatorio::pendientes()->count(),
            'vencidos' => Recordatorio::vencidos()->count(),
        ];

        return response()->json([
            'recordatorios' => $recordatorios,
            'contadores' => $contadores,
        ]);
    }
}
