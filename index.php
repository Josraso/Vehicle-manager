<?php
/**
 * Vehicle Manager - Router Principal
 *
 * Punto de entrada único de la aplicación.
 * Gestiona todas las rutas y carga los controladores correspondientes.
 */

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Iniciar sesión
session_start();

// Verificar expiración de sesión
if (isset($_SESSION['session_expires']) && $_SESSION['session_expires'] < time()) {
    session_destroy();
    session_start();
}

// Refrescar cookie si es sesión con "recuerdo mi usuario"
if (isset($_SESSION['remember_me']) && $_SESSION['remember_me']) {
    $_SESSION['session_expires'] = time() + (30 * 24 * 60 * 60);
    setcookie(session_name(), session_id(), [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Cargar clases Core
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Validator.php';

// Cargar Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Vehicle.php';
require_once __DIR__ . '/models/FuelLog.php';
require_once __DIR__ . '/models/MaintenanceLog.php';
require_once __DIR__ . '/models/OdometerLog.php';
require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/models/EmailTemplate.php';
require_once __DIR__ . '/models/ActivityLog.php';
require_once __DIR__ . '/models/EmailQueue.php';
require_once __DIR__ . '/models/Ticket.php';

// Cargar Core adicional
require_once __DIR__ . '/core/Mailer.php';

// Cargar Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/VehicleController.php';
require_once __DIR__ . '/controllers/FuelController.php';
require_once __DIR__ . '/controllers/MaintenanceController.php';
require_once __DIR__ . '/controllers/OdometerController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/StatsController.php';
require_once __DIR__ . '/controllers/ExportController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/TicketController.php';
require_once __DIR__ . '/controllers/CronController.php';

// Obtener acción de la URL
$action = $_GET['action'] ?? 'login';

// Definir rutas
$routes = [
    // Autenticación
    'login' => ['AuthController', 'login'],
    'register' => ['AuthController', 'register'],
    'logout' => ['AuthController', 'logout'],
    'forgot_password' => ['AuthController', 'forgotPassword'],
    'reset_password' => ['AuthController', 'resetPassword'],

    // Dashboard y Vehículos
    'dashboard' => ['VehicleController', 'index'],
    'vehicle_show' => ['VehicleController', 'show'],
    'vehicle_create' => ['VehicleController', 'create'],
    'vehicle_edit' => ['VehicleController', 'edit'],
    'vehicle_delete' => ['VehicleController', 'delete'],

    // Repostajes
    'fuel_create' => ['FuelController', 'create'],
    'fuel_edit' => ['FuelController', 'edit'],
    'fuel_delete' => ['FuelController', 'delete'],

    // Mantenimientos
    'maintenance_create' => ['MaintenanceController', 'create'],
    'maintenance_edit' => ['MaintenanceController', 'edit'],
    'maintenance_delete' => ['MaintenanceController', 'delete'],

    // Kilometraje
    'odometer_index' => ['OdometerController', 'index'],
    'odometer_create' => ['OdometerController', 'create'],
    'odometer_delete' => ['OdometerController', 'delete'],

    // Perfil
    'profile' => ['ProfileController', 'index'],
    'toggle_theme' => ['ProfileController', 'toggleTheme'],

    // Estadísticas
    'stats' => ['StatsController', 'index'],
    'stats_data' => ['StatsController', 'getData'],

    // Exportación
    'export_fuel_csv' => ['ExportController', 'fuelCsv'],
    'export_maintenance_csv' => ['ExportController', 'maintenanceCsv'],
    'export_backup_csv' => ['ExportController', 'backupCsv'],
    'export_pdf' => ['ExportController', 'pdf'],

    // Administración
    'admin_dashboard' => ['AdminController', 'dashboard'],
    'admin_users' => ['AdminController', 'users'],
    'admin_user_edit' => ['AdminController', 'userEdit'],
    'admin_user_toggle' => ['AdminController', 'userToggle'],
    'admin_user_delete' => ['AdminController', 'userDelete'],
    'admin_settings' => ['AdminController', 'settings'],
    'admin_email' => ['AdminController', 'email'],
    'admin_email_test' => ['AdminController', 'emailTest'],
    'admin_templates' => ['AdminController', 'templates'],
    'admin_template_edit' => ['AdminController', 'templateEdit'],
    'admin_logs' => ['AdminController', 'logs'],
    'admin_logs_clear' => ['AdminController', 'logsClear'],
    'admin_logs_stats' => ['AdminController', 'logStats'],
    'admin_logs_export' => ['AdminController', 'logExport'],
    'admin_impersonate' => ['AdminController', 'impersonate'],
    'admin_impersonate_end' => ['AdminController', 'impersonateEnd'],
    'admin_tickets' => ['AdminController', 'tickets'],
    'admin_ticket_detail' => ['AdminController', 'ticketDetail'],
    'admin_ticket_reply' => ['AdminController', 'ticketReply'],
    'admin_ticket_close' => ['AdminController', 'ticketClose'],
    'admin_health' => ['AdminController', 'health'],

    // Tickets (usuario)
    'tickets' => ['TicketController', 'index'],
    'ticket_create' => ['TicketController', 'create'],

    // Cron
    'cron_queue' => ['CronController', 'processQueue'],
];

// Ejecutar ruta
try {
    if (isset($routes[$action])) {
        [$controllerName, $method] = $routes[$action];
        $controller = new $controllerName();
        $controller->$method();
    } else {
        // Ruta no encontrada, redirigir a login
        header('Location: index.php?action=login');
        exit;
    }
} catch (PDOException $e) {
    // Error de base de datos
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error de Configuración</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Error de Base de Datos</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">No se pudo conectar a la base de datos. Por favor, verifica:</p>
                                <ol>
                                    <li>MySQL/MariaDB está ejecutándose</li>
                                    <li>La base de datos <code>vehicle_manager</code> existe</li>
                                    <li>Las credenciales en <code>config/database.php</code> son correctas</li>
                                </ol>
                                <hr>
                                <p class="mb-2"><strong>Para crear la base de datos:</strong></p>
                                <pre class="bg-dark text-light p-3 rounded">mysql -u root -p < database.sql</pre>
                                <p class="text-muted small mt-3">Error técnico: ' . htmlspecialchars($e->getMessage()) . '</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
    throw $e;
} catch (Exception $e) {
    // Otros errores
    error_log('Vehicle Manager Error: ' . $e->getMessage());

    if (ini_get('display_errors')) {
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        header('Location: index.php?action=login');
    }
    exit;
}
