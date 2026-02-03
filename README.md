# Sistema Financiero Unificado

Sistema completo de gestión financiera personal y empresarial desarrollado con Laravel 12.

## Características

- **Dashboard Financiero**: Vista general de saldos, ingresos y egresos
- **Gestión de Cuentas Bancarias**: Múltiples cuentas con seguimiento de saldos
- **Movimientos**: Registro de ingresos y egresos con categorización
- **Proyección de Flujo de Efectivo**: Análisis predictivo a 30, 60 y 90 días
- **Pagos Programados**: Gestión de pagos recurrentes y únicos
- **Préstamos**: Control de préstamos y cuotas
- **Metas Financieras**: Seguimiento de objetivos de ahorro
- **Reportes**: Análisis detallado por período y categoría
- **Escenarios**: Proyecciones optimista, pesimista y realista

## Stack Tecnológico

- **Backend**: Laravel 12 + PHP 8.2
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Base de Datos**: SQLite (desarrollo) / PostgreSQL (producción)
- **Gráficas**: Chart.js

## Instalación Local

```bash
# Clonar repositorio
git clone https://github.com/gchernitsky2/sistema-financiero-unificado.git
cd sistema-financiero-unificado

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Crear base de datos SQLite
touch database/database.sqlite

# Ejecutar migraciones
php artisan migrate --seed

# Iniciar servidor
php artisan serve
```

## Despliegue en Railway

1. Conecta tu repositorio de GitHub a Railway
2. Railway detectará automáticamente la configuración
3. Agrega las siguientes variables de entorno:
   - `APP_KEY`: Genera con `php artisan key:generate --show`
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_URL`: La URL de tu app en Railway
4. Agrega un servicio PostgreSQL desde Railway
5. Las variables de BD se configurarán automáticamente

## Variables de Entorno Requeridas

```env
APP_NAME="Sistema Financiero Unificado"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://tu-app.railway.app

DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...

SESSION_DRIVER=database
CACHE_STORE=file
```

## Estructura del Proyecto

```
app/
├── Http/Controllers/     # Controladores
├── Models/              # Modelos Eloquent
├── Services/            # Servicios de negocio
resources/
├── views/               # Vistas Blade
routes/
├── web.php              # Rutas web
database/
├── migrations/          # Migraciones
├── seeders/            # Seeders de datos
```

## Licencia

MIT License
