<?php
/**
 * Controller Cron - Recordatorios de mantenimiento + procesamiento de cola
 *
 * Endpoint protegido por token. Se ejecuta mediante un cron del servidor.
 * Ejemplo: * * * * * curl -s "http://tudominio/index.php?action=cron_queue&token=TOKEN" > /dev/null 2>&1
 *
 * Flujo por cada ejecución:
 *   1. Detecta mantenimientos próximos a vencer → los encola como emails pendientes
 *   2. Procesa la cola email_queue → envía los emails por SMTP
 */

class CronController extends Controller
{
    /**
     * Punto de entrada del cron
     */
    public function processQueue(): void
    {
        $config = require __DIR__ . '/../config/app.php';
        $token = $this->get('token', '');

        if (!$token || $token !== ($config['cron']['token'] ?? '')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Token inválido']);
            exit;
        }

        // Paso 1: detectar mantenimientos próximos y encolar recordatorios
        $remindersEnqueued = $this->enqueueMaintenanceReminders();

        // Paso 2: procesar cola de emails (incluye los recordatorios que se acaban de encolar)
        $queue = new EmailQueue();
        $mailer = new Mailer();

        $pending = $queue->getPending(50);
        $processed = 0;
        $failed = 0;

        foreach ($pending as $email) {
            $result = $mailer->send(
                $email['to_email'],
                $email['to_name'] ?? '',
                $email['subject'],
                $email['body']
            );

            if ($result) {
                $queue->markSent($email['id']);
                $processed++;
            } else {
                $queue->markFailed($email['id'], $mailer->getLastError() ?? 'Error desconocido');
                $failed++;
            }
        }

        // Limpiar emails antiguos (enviados/fallidos de hace más de 30 días)
        $queue->cleanOld(30);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'reminders_enqueued' => $remindersEnqueued,
            'processed' => $processed,
            'failed' => $failed,
            'pending' => count($pending),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Detecta mantenimientos próximos a vencer y encola un recordatorio por email
     * para cada uno. Usa sent_reminders para no enviar duplicados en un período
     * de 7 días.
     */
    private function enqueueMaintenanceReminders(): int
    {
        $settingModel = new Setting();

        if (!$settingModel->get('reminder_enabled', true)) {
            return 0;
        }

        $reminderDays = (int) $settingModel->get('reminder_days_before', 7);
        $db = Database::getInstance();
        $mailer = new Mailer();
        $enqueued = 0;

        $today = date('Y-m-d');
        $reminderDate = date('Y-m-d', strtotime("+{$reminderDays} days"));

        // Usuarios con notificaciones activas
        $stmt = $db->prepare("SELECT * FROM users WHERE is_active = 1 AND email_notifications = 1");
        $stmt->execute();
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $vehicles = $stmt->fetchAll();

            foreach ($vehicles as $vehicle) {
                $reminderKm = $vehicle['current_km'] + 500;

                // Mantenimientos que vencen dentro del período de recordatorio
                // y que NO han sido notificados en los últimos 7 días
                $sql = "SELECT m.*, mt.name as type_name
                        FROM maintenance_logs m
                        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                        LEFT JOIN sent_reminders sr
                            ON m.id = sr.maintenance_log_id
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
                $pendingReminders = $stmt->fetchAll();

                foreach ($pendingReminders as $maintenance) {
                    $variables = [
                        'user_name' => $user['name'],
                        'vehicle_name' => $vehicle['brand'] . ' ' . $vehicle['model'],
                        'maintenance_type' => $maintenance['type_name'] ?? 'Mantenimiento',
                        'due_date' => $maintenance['next_date']
                            ? date('d/m/Y', strtotime($maintenance['next_date']))
                            : 'N/A',
                        'due_km' => $maintenance['next_km']
                            ? number_format($maintenance['next_km']) . ' km'
                            : 'N/A'
                    ];

                    $queued = $mailer->sendTemplateToQueue(
                        'maintenance_reminder',
                        $user['email'],
                        $user['name'],
                        $variables
                    );

                    if ($queued) {
                        // Registrar en sent_reminders para bloquear duplicados 7 días
                        $db->prepare(
                            "INSERT INTO sent_reminders (maintenance_log_id, user_id, reminder_type) VALUES (?, ?, 'email')"
                        )->execute([$maintenance['id'], $user['id']]);

                        $db->prepare(
                            "UPDATE maintenance_logs SET reminder_sent = 1 WHERE id = ?"
                        )->execute([$maintenance['id']]);

                        $enqueued++;
                    }
                }
            }
        }

        return $enqueued;
    }
}
