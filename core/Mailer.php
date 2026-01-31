<?php
/**
 * Clase Mailer - Envío de emails (SMTP, Sendmail, mail)
 */

class Mailer
{
    private array $config;
    private array $errors = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Cargar configuración desde la BD
     */
    private function loadConfig(): void
    {
        $settingModel = new Setting();

        $this->config = [
            'driver' => $settingModel->get('mail_driver', 'mail'),
            'host' => $settingModel->get('mail_host', 'localhost'),
            'port' => (int) $settingModel->get('mail_port', 587),
            'username' => $settingModel->get('mail_username', ''),
            'password' => $settingModel->get('mail_password', ''),
            'encryption' => $settingModel->get('mail_encryption', 'tls'),
            'from_address' => $settingModel->get('mail_from_address', 'noreply@example.com'),
            'from_name' => $settingModel->get('mail_from_name', 'Vehicle Manager'),
        ];
    }

    /**
     * Enviar email
     */
    public function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $this->errors = [];

        // Validar email
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Email de destino no válido';
            return false;
        }

        // Preparar headers
        $headers = $this->buildHeaders();

        // Enviar según driver
        switch ($this->config['driver']) {
            case 'smtp':
                return $this->sendViaSMTP($toEmail, $toName, $subject, $body);
            case 'sendmail':
                return $this->sendViaSendmail($toEmail, $toName, $subject, $body, $headers);
            case 'mail':
            default:
                return $this->sendViaMail($toEmail, $toName, $subject, $body, $headers);
        }
    }

    /**
     * Enviar via SMTP
     */
    private function sendViaSMTP(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        $encryption = $this->config['encryption'];

        // Determinar prefijo de conexión
        $prefix = '';
        if ($encryption === 'ssl') {
            $prefix = 'ssl://';
        } elseif ($encryption === 'tls') {
            $prefix = 'tls://';
        }

        // Intentar conexión
        $errno = 0;
        $errstr = '';
        $timeout = 30;

        // Para TLS, primero conectamos sin encriptación
        if ($encryption === 'tls') {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            $this->errors[] = "No se pudo conectar al servidor SMTP: {$errstr} ({$errno})";
            return false;
        }

        // Función para leer respuesta
        $getResponse = function() use ($socket) {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $response;
        };

        // Función para enviar comando
        $sendCommand = function($command, $expectedCode = null) use ($socket, $getResponse) {
            fwrite($socket, $command . "\r\n");
            $response = $getResponse();

            if ($expectedCode && substr($response, 0, 3) != $expectedCode) {
                return false;
            }
            return $response;
        };

        try {
            // Leer saludo
            $response = $getResponse();
            if (substr($response, 0, 3) != '220') {
                throw new Exception('Respuesta inesperada del servidor');
            }

            // EHLO
            $response = $sendCommand('EHLO ' . gethostname());

            // STARTTLS para TLS
            if ($encryption === 'tls') {
                $sendCommand('STARTTLS', '220');
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $sendCommand('EHLO ' . gethostname());
            }

            // AUTH LOGIN
            if ($username && $password) {
                $sendCommand('AUTH LOGIN', '334');
                $sendCommand(base64_encode($username), '334');
                $response = $sendCommand(base64_encode($password));
                if (substr($response, 0, 3) != '235') {
                    throw new Exception('Error de autenticación SMTP');
                }
            }

            // MAIL FROM
            $fromAddress = $this->config['from_address'];
            $response = $sendCommand("MAIL FROM:<{$fromAddress}>");
            if (substr($response, 0, 3) != '250') {
                throw new Exception('Error en MAIL FROM');
            }

            // RCPT TO
            $response = $sendCommand("RCPT TO:<{$toEmail}>");
            if (substr($response, 0, 3) != '250') {
                throw new Exception('Error en RCPT TO');
            }

            // DATA
            $sendCommand('DATA', '354');

            // Construir mensaje
            $message = $this->buildMessage($toEmail, $toName, $subject, $body);
            fwrite($socket, $message . "\r\n.\r\n");

            $response = $getResponse();
            if (substr($response, 0, 3) != '250') {
                throw new Exception('Error al enviar mensaje');
            }

            // QUIT
            $sendCommand('QUIT');
            fclose($socket);

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'Error SMTP: ' . $e->getMessage();
            if (is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    /**
     * Enviar via Sendmail
     */
    private function sendViaSendmail(string $toEmail, string $toName, string $subject, string $body, string $headers): bool
    {
        $sendmailPath = '/usr/sbin/sendmail -t -i';

        $message = $this->buildMessage($toEmail, $toName, $subject, $body);

        $process = popen($sendmailPath, 'w');
        if (!$process) {
            $this->errors[] = 'No se pudo abrir sendmail';
            return false;
        }

        fwrite($process, $message);
        $result = pclose($process);

        if ($result !== 0) {
            $this->errors[] = 'Error al enviar con sendmail';
            return false;
        }

        return true;
    }

    /**
     * Enviar via mail() de PHP
     */
    private function sendViaMail(string $toEmail, string $toName, string $subject, string $body, string $headers): bool
    {
        $to = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        $result = @mail($to, $subject, $body, $headers);

        if (!$result) {
            $this->errors[] = 'Error al enviar con mail()';
            return false;
        }

        return true;
    }

    /**
     * Construir headers
     */
    private function buildHeaders(): string
    {
        $fromAddress = $this->config['from_address'];
        $fromName = $this->config['from_name'];

        $headers = [];
        $headers[] = "From: {$fromName} <{$fromAddress}>";
        $headers[] = "Reply-To: {$fromAddress}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "X-Mailer: VehicleManager/1.0";

        return implode("\r\n", $headers);
    }

    /**
     * Construir mensaje completo
     */
    private function buildMessage(string $toEmail, string $toName, string $subject, string $body): string
    {
        $fromAddress = $this->config['from_address'];
        $fromName = $this->config['from_name'];
        $to = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        $message = [];
        $message[] = "To: {$to}";
        $message[] = "From: {$fromName} <{$fromAddress}>";
        $message[] = "Subject: {$subject}";
        $message[] = "MIME-Version: 1.0";
        $message[] = "Content-Type: text/html; charset=UTF-8";
        $message[] = "";
        $message[] = $body;

        return implode("\r\n", $message);
    }

    /**
     * Enviar email usando plantilla
     */
    public function sendTemplate(string $templateSlug, string $toEmail, string $toName, array $variables = []): bool
    {
        $templateModel = new EmailTemplate();
        $template = $templateModel->findBySlug($templateSlug);

        if (!$template || !$template['is_active']) {
            $this->errors[] = 'Plantilla no encontrada o inactiva';
            return false;
        }

        // Añadir variables globales
        $settingModel = new Setting();
        $variables['site_name'] = $settingModel->get('site_name', 'Vehicle Manager');
        $variables['site_url'] = $this->getSiteUrl();
        $variables['date'] = date('d/m/Y H:i');

        // Reemplazar variables en subject y body
        $subject = $this->replaceVariables($template['subject'], $variables);
        $body = $this->replaceVariables($template['body'], $variables);

        // Envolver en template HTML
        $body = $this->wrapInHtmlTemplate($body);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    /**
     * Reemplazar variables en texto
     */
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Envolver contenido en template HTML
     */
    private function wrapInHtmlTemplate(string $content): string
    {
        $settingModel = new Setting();
        $siteName = $settingModel->get('site_name', 'Vehicle Manager');

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#333;background:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background:#0d6efd;padding:20px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;">' . htmlspecialchars($siteName) . '</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            ' . $content . '
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8f9fa;padding:15px;text-align:center;font-size:12px;color:#666;">
                            <p style="margin:0;">&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. Todos los derechos reservados.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Obtener URL del sitio
     */
    private function getSiteUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim("{$protocol}://{$host}{$path}", '/');
    }

    /**
     * Probar conexión de email
     */
    public function testConnection(): array
    {
        $result = [
            'success' => false,
            'driver' => $this->config['driver'],
            'message' => '',
            'details' => []
        ];

        switch ($this->config['driver']) {
            case 'smtp':
                $result = $this->testSMTPConnection();
                break;
            case 'sendmail':
                $result = $this->testSendmailConnection();
                break;
            case 'mail':
                $result = $this->testMailConnection();
                break;
        }

        return $result;
    }

    /**
     * Probar conexión SMTP
     */
    private function testSMTPConnection(): array
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $encryption = $this->config['encryption'];

        $result = [
            'success' => false,
            'driver' => 'smtp',
            'message' => '',
            'details' => [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption
            ]
        ];

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);

        if (!$socket) {
            $result['message'] = "No se pudo conectar: {$errstr}";
            return $result;
        }

        $response = fgets($socket, 515);
        fclose($socket);

        if (substr($response, 0, 3) === '220') {
            $result['success'] = true;
            $result['message'] = 'Conexión SMTP exitosa';
            $result['details']['server_response'] = trim($response);
        } else {
            $result['message'] = 'Respuesta inesperada del servidor';
        }

        return $result;
    }

    /**
     * Probar sendmail
     */
    private function testSendmailConnection(): array
    {
        $sendmailPath = '/usr/sbin/sendmail';

        $result = [
            'success' => false,
            'driver' => 'sendmail',
            'message' => '',
            'details' => ['path' => $sendmailPath]
        ];

        if (file_exists($sendmailPath) && is_executable($sendmailPath)) {
            $result['success'] = true;
            $result['message'] = 'Sendmail está disponible';
        } else {
            $result['message'] = 'Sendmail no encontrado o no ejecutable';
        }

        return $result;
    }

    /**
     * Probar mail()
     */
    private function testMailConnection(): array
    {
        $result = [
            'success' => false,
            'driver' => 'mail',
            'message' => '',
            'details' => []
        ];

        if (function_exists('mail')) {
            $result['success'] = true;
            $result['message'] = 'Función mail() disponible';
        } else {
            $result['message'] = 'Función mail() no disponible';
        }

        return $result;
    }

    /**
     * Obtener errores
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener último error
     */
    public function getLastError(): ?string
    {
        return !empty($this->errors) ? end($this->errors) : null;
    }

    /**
     * Obtener configuración actual
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
