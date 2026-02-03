<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banco;
use App\Models\Categoria;
use App\Models\TipoMovimiento;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        // Bancos mexicanos principales
        $bancos = [
            ['nombre' => 'BBVA México', 'nombre_corto' => 'BBVA', 'codigo' => '012', 'color' => '#004481'],
            ['nombre' => 'Citibanamex', 'nombre_corto' => 'Banamex', 'codigo' => '002', 'color' => '#1E4A88'],
            ['nombre' => 'Santander México', 'nombre_corto' => 'Santander', 'codigo' => '014', 'color' => '#EC0000'],
            ['nombre' => 'Banorte', 'nombre_corto' => 'Banorte', 'codigo' => '072', 'color' => '#CB0D28'],
            ['nombre' => 'HSBC México', 'nombre_corto' => 'HSBC', 'codigo' => '021', 'color' => '#DB0011'],
            ['nombre' => 'Scotiabank', 'nombre_corto' => 'Scotia', 'codigo' => '044', 'color' => '#EC111A'],
            ['nombre' => 'Banco Azteca', 'nombre_corto' => 'Azteca', 'codigo' => '127', 'color' => '#00A651'],
            ['nombre' => 'Inbursa', 'nombre_corto' => 'Inbursa', 'codigo' => '036', 'color' => '#1A3668'],
            ['nombre' => 'Banregio', 'nombre_corto' => 'Banregio', 'codigo' => '058', 'color' => '#E31B23'],
            ['nombre' => 'Banco del Bajío', 'nombre_corto' => 'BanBajío', 'codigo' => '030', 'color' => '#003366'],
            ['nombre' => 'Nu México', 'nombre_corto' => 'Nu', 'codigo' => '638', 'color' => '#820AD1'],
            ['nombre' => 'Hey Banco', 'nombre_corto' => 'Hey', 'codigo' => '072', 'color' => '#00D4AA'],
        ];

        foreach ($bancos as $banco) {
            Banco::firstOrCreate(['codigo' => $banco['codigo']], $banco);
        }

        // Categorías de ingresos
        $categoriasIngreso = [
            ['nombre' => 'Salario', 'codigo' => 'ING001', 'tipo' => 'ingreso', 'color' => '#10B981', 'icono' => '💰'],
            ['nombre' => 'Honorarios', 'codigo' => 'ING002', 'tipo' => 'ingreso', 'color' => '#059669', 'icono' => '📋'],
            ['nombre' => 'Comisiones', 'codigo' => 'ING003', 'tipo' => 'ingreso', 'color' => '#047857', 'icono' => '💵'],
            ['nombre' => 'Ventas', 'codigo' => 'ING004', 'tipo' => 'ingreso', 'color' => '#065F46', 'icono' => '🛒'],
            ['nombre' => 'Intereses', 'codigo' => 'ING005', 'tipo' => 'ingreso', 'color' => '#34D399', 'icono' => '📈'],
            ['nombre' => 'Rentas cobradas', 'codigo' => 'ING006', 'tipo' => 'ingreso', 'color' => '#6EE7B7', 'icono' => '🏠'],
            ['nombre' => 'Préstamos recibidos', 'codigo' => 'ING007', 'tipo' => 'ingreso', 'color' => '#A7F3D0', 'icono' => '🤝'],
            ['nombre' => 'Otros ingresos', 'codigo' => 'ING099', 'tipo' => 'ingreso', 'color' => '#D1FAE5', 'icono' => '✨'],
        ];

        // Categorías de egresos
        $categoriasEgreso = [
            ['nombre' => 'Alimentación', 'codigo' => 'EGR001', 'tipo' => 'egreso', 'color' => '#EF4444', 'icono' => '🍽️'],
            ['nombre' => 'Transporte', 'codigo' => 'EGR002', 'tipo' => 'egreso', 'color' => '#F97316', 'icono' => '🚗'],
            ['nombre' => 'Servicios', 'codigo' => 'EGR003', 'tipo' => 'egreso', 'color' => '#F59E0B', 'icono' => '💡'],
            ['nombre' => 'Vivienda', 'codigo' => 'EGR004', 'tipo' => 'egreso', 'color' => '#EAB308', 'icono' => '🏠'],
            ['nombre' => 'Salud', 'codigo' => 'EGR005', 'tipo' => 'egreso', 'color' => '#84CC16', 'icono' => '🏥'],
            ['nombre' => 'Educación', 'codigo' => 'EGR006', 'tipo' => 'egreso', 'color' => '#22C55E', 'icono' => '📚'],
            ['nombre' => 'Entretenimiento', 'codigo' => 'EGR007', 'tipo' => 'egreso', 'color' => '#14B8A6', 'icono' => '🎬'],
            ['nombre' => 'Ropa y calzado', 'codigo' => 'EGR008', 'tipo' => 'egreso', 'color' => '#06B6D4', 'icono' => '👕'],
            ['nombre' => 'Seguros', 'codigo' => 'EGR009', 'tipo' => 'egreso', 'color' => '#0EA5E9', 'icono' => '🛡️'],
            ['nombre' => 'Impuestos', 'codigo' => 'EGR010', 'tipo' => 'egreso', 'color' => '#3B82F6', 'icono' => '📝'],
            ['nombre' => 'Nómina', 'codigo' => 'EGR011', 'tipo' => 'egreso', 'color' => '#6366F1', 'icono' => '👥'],
            ['nombre' => 'Proveedores', 'codigo' => 'EGR012', 'tipo' => 'egreso', 'color' => '#8B5CF6', 'icono' => '📦'],
            ['nombre' => 'Tarjetas de crédito', 'codigo' => 'EGR013', 'tipo' => 'egreso', 'color' => '#A855F7', 'icono' => '💳'],
            ['nombre' => 'Préstamos pagados', 'codigo' => 'EGR014', 'tipo' => 'egreso', 'color' => '#D946EF', 'icono' => '🏦'],
            ['nombre' => 'Otros gastos', 'codigo' => 'EGR099', 'tipo' => 'egreso', 'color' => '#EC4899', 'icono' => '📎'],
        ];

        // Categorías transferencias
        $categoriasTransferencia = [
            ['nombre' => 'Transferencia entre cuentas', 'codigo' => 'TRF001', 'tipo' => 'ambos', 'color' => '#6B7280', 'icono' => '🔄'],
            ['nombre' => 'Ajuste de saldo', 'codigo' => 'TRF002', 'tipo' => 'ambos', 'color' => '#9CA3AF', 'icono' => '⚖️'],
        ];

        $todasCategorias = array_merge($categoriasIngreso, $categoriasEgreso, $categoriasTransferencia);

        foreach ($todasCategorias as $categoria) {
            Categoria::firstOrCreate(
                ['codigo' => $categoria['codigo']],
                array_merge($categoria, ['activa' => true])
            );
        }

        // Tipos de movimiento
        $tiposMovimiento = [
            ['nombre' => 'Cargo', 'codigo' => 'cargo', 'naturaleza' => 'cargo', 'descripcion' => 'Salida de dinero'],
            ['nombre' => 'Abono', 'codigo' => 'abono', 'naturaleza' => 'abono', 'descripcion' => 'Entrada de dinero'],
        ];

        foreach ($tiposMovimiento as $tipo) {
            TipoMovimiento::firstOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }

        $this->command->info('Datos iniciales creados correctamente.');
    }
}
