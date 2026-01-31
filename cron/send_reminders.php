<?php
/**
 * CRON Script - Envío de recordatorios de mantenimiento
 *
 * Ejecutar diariamente:
 * 0 8 * * * php /ruta/al/proyecto/cron/send_reminders.php
 *
 * Este script busca mantenimientos próximos a vencer y envía
 * recordatorios por email a los usuarios que tienen activadas
 * las notificaciones.
 */

// Definir entorno CLI
define('IS_CRON', true);

// Cambiar al directorio del proyecto
chdir(dirname(__DIR__));

// Cargar dependencias
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/EmailTemplate.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/MaintenanceLog.php';
require_once __DIR__ . '/../models/ActivityLog.php';

// Zona horaria
date_default_timezone_set('Europe/Madrid');

/**
 * Clase para gestionar el envío de recordatorios
 */
class ReminderSender
{
    private Setting $settingModel;
    private User $userModel;
    private Vehicle $vehicleModel;
    private MaintenanceLog $maintenanceModel;
    private ActivityLog $activityModel;
    private Mailer $mailer;
    private int $reminderDays;
    private bool $isEnabled;
    private int $sentCount = 0;
    private int $errorCount = 0;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->userModel = new User();
        $this->vehicleModel = new Vehicle();
        $this->maintenanceModel = new MaintenanceLog();
        $this->activityModel = new ActivityLog();
        $this->mailer = new Mailer();

        // Cargar configuración
        $this->isEnabled = (bool) $this->settingModel->get('reminder_enabled', true);
        $this->reminderDays = (int) $this->settingModel->get('reminder_days_before', 7);
    }

    /**
     * Ejecutar el proceso de envío de recordatorios
     */
    public function run(): void
    {
        $this->log("=== Inicio del proceso de recordatorios ===");
        $this->log("Fecha: " . date('Y-m-d H:i:s'));

        // Verificar si está habilitado
        if (!$this->isEnabled) {
            $this->log("Los recordatorios están desactivados en la configuración.");
            return;
        }

        // Obtener usuarios con notificaciones activas
        $users = $this->getUsersWithNotifications();
        $this->log("Usuarios con notificaciones activas: " . count($users));

        foreach ($users as $user) {
            $this->processUser($user);
        }

        $this->log("=== Proceso finalizado ===");
        $this->log("Recordatorios enviados: {$this->sentCount}");
        $this->log("Errores: {$this->errorCount}");

        // Registrar en activity log
        if ($this->sentCount > 0 || $this->errorCount > 0) {
            $this->activityModel->log(
                'cron_reminders',
                "Recordatorios enviados: {$this->sentCount}, Errores: {$this->errorCount}"
            );
        }
    }

    /**
     * Obtener usuarios con notificaciones activas
     */
    private function getUsersWithNotifications(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE is_active = 1 AND email_notifications = 1";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Procesar recordatorios para un usuario
     */
    private function processUser(array $user): void
    {
        $this->log("Procesando usuario: {$user['email']}");

        // Obtener vehículos del usuario
        $vehicles = $this->vehicleModel->getByUser($user['id']);

        foreach ($vehicles as $vehicle) {
            $this->processVehicle($user, $vehicle);
        }
    }

    /**
     * Procesar recordatorios para un vehículo
     */
    private function processVehicle(array $user, array $vehicle): void
    {
        $pendingReminders = $this->getPendingReminders($vehicle);

        foreach ($pendingReminders as $maintenance) {
            $this->sendReminder($user, $vehicle, $maintenance);
        }
    }

    /**
     * Obtener mantenimientos pendientes de recordatorio
     */
    private function getPendingReminders(array $vehicle): array
    {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $reminderDate = date('Y-m-d', strtotime("+{$this->reminderDays} days"));
        $reminderKm = $vehicle['current_km'] + 500; // 500 km de margen

        $sql = "SELECT m.*, mt.name as type_name
                FROM maintenance_logs m
                LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                LEFT JOIN sent_reminders sr ON m.id = sr.maintenance_log_id
                    AND sr.reminder_type = 'email'
                    AND sr.sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                WHERE m.vehicle_id = ?
                AND sr.id IS NULL
                AND (
                    (m.next_date IS NOT NULL AND m.next_date <= ? AND m.next_date >= ?)
                    OR (m.next_km IS NOT NULL AND m.next_km <= ?)
                )
                ORDER BY COALESCE(m.next_date, '9999-12-31'), m.next_km";

        $stmt = $db->prepare($sql);
        $stmt->execute([$vehicle['id'], $reminderDate, $today, $reminderKm]);
        return $stmt->fetchAll();
    }

    /**
     * Enviar recordatorio de mantenimiento
     */
    private function sendReminder(array $user, array $vehicle, array $maintenance): void
    {
        $siteName = $this->settingModel->get('site_name', 'Vehicle Manager');

        // Preparar variables para la plantilla
        $variables = [
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'vehicle_name' => $vehicle['brand'] . ' ' . $vehicle['model'],
            'vehicle_plate' => $vehicle['plate'],
            'maintenance_type' => $maintenance['type_name'] ?? 'Mantenimiento',
            'due_date' => $maintenance['next_date'] ? date('d/m/Y', strtotime($maintenance['next_date'])) : '',
            'due_km' => $maintenance['next_km'] ? number_format($maintenance['next_km']) . ' km' : '',
            'current_km' => number_format($vehicle['current_km']) . ' km',
            'site_name' => $siteName
        ];

        // Intentar enviar usando plantilla
        $result = $this->mailer->sendTemplate(
            'maintenance_reminder',
            $user['email'],
            $user['name'],
            $variables
        );

        if ($result) {
            $this->sentCount++;
            $this->log("  ✓ Recordatorio enviado: {$maintenance['type_name']} - {$vehicle['plate']}");

            // Registrar envío
            $this->recordSentReminder($maintenance['id'], $user['id']);
        } else {
            $this->errorCount++;
            $this->log("  ✗ Error al enviar: {$maintenance['type_name']} - " . $this->mailer->getLastError());
        }
    }

    /**
     * Registrar recordatorio enviado
     */
    private function recordSentReminder(int $maintenanceLogId, int $userId): void
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO sent_reminders (maintenance_log_id, user_id, reminder_type, sent_at)
                VALUES (?, ?, 'email', NOW())";
        $db->prepare($sql)->execute([$maintenanceLogId, $userId]);

        // Marcar en el log de mantenimiento
        $sql = "UPDATE maintenance_logs SET reminder_sent = 1 WHERE id = ?";
        $db->prepare($sql)->execute([$maintenanceLogId]);
    }

    /**
     * Log de consola
     */
    private function log(string $message): void
    {
        echo date('[H:i:s] ') . $message . PHP_EOL;
    }
}

// Ejecutar
try {
    $sender = new ReminderSender();
    $sender->run();
} catch (Exception $e) {
    echo "Error fatal: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
