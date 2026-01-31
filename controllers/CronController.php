<?php
/**
 * Controller Cron - Procesamiento de cola de emails
 *
 * Endpoint protegido por token. Se ejecuta mediante un cron del servidor.
 * Ejemplo: * * * * * curl -s "http://tudominio/index.php?action=cron_queue&token=TOKEN" > /dev/null 2>&1
 */

class CronController extends Controller
{
    /**
     * Procesar emails pendientes en la cola
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

        $queue = new EmailQueue();
        $mailer = new Mailer();

        $pending = $queue->getPending(20);
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
            'processed' => $processed,
            'failed' => $failed,
            'pending' => count($pending),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
