<?php
/**
 * Controller de Administración
 */

class AdminController extends Controller
{
    private User $userModel;
    private Vehicle $vehicleModel;
    private Setting $settingModel;
    private EmailTemplate $templateModel;
    private ActivityLog $activityModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->vehicleModel = new Vehicle();
        $this->settingModel = new Setting();
        $this->templateModel = new EmailTemplate();
        $this->activityModel = new ActivityLog();
    }

    /**
     * Dashboard de administración
     */
    public function dashboard(): void
    {
        Auth::requireAdmin();

        // Estadísticas generales
        $userStats = $this->userModel->getStats();

        // Total vehículos
        $db = Database::getInstance();
        $totalVehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        $totalFuelLogs = $db->query("SELECT COUNT(*) FROM fuel_logs")->fetchColumn();
        $totalMaintenanceLogs = $db->query("SELECT COUNT(*) FROM maintenance_logs")->fetchColumn();

        // Gastos totales
        $totalFuelCost = $db->query("SELECT COALESCE(SUM(total_cost), 0) FROM fuel_logs")->fetchColumn();
        $totalMaintCost = $db->query("SELECT COALESCE(SUM(cost), 0) FROM maintenance_logs")->fetchColumn();

        // Actividad reciente
        $recentActivity = $this->activityModel->getRecent(10);

        // Alertas de mantenimiento próximos (global)
        $reminderDays = max((int) $this->settingModel->get('reminder_days_before', 7), 14);
        $maintenanceAlerts = $db->query(
            "SELECT m.id, u.name as user_name, v.brand, v.model, v.license_plate, v.current_km,
                    mt.name as maintenance_type, m.next_date, m.next_km
             FROM maintenance_logs m
             JOIN vehicles v ON m.vehicle_id = v.id
             JOIN users u ON v.user_id = u.id
             LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
             WHERE (
                 (m.next_date IS NOT NULL AND m.next_date <= DATE_ADD(NOW(), INTERVAL {$reminderDays} DAY))
                 OR (m.next_km IS NOT NULL AND m.next_km <= v.current_km + 500)
             )
             ORDER BY COALESCE(m.next_date, '9999-12-31') ASC
             LIMIT 15"
        )->fetchAll();

        $this->render('admin/dashboard', [
            'userStats' => $userStats,
            'totalVehicles' => $totalVehicles,
            'totalFuelLogs' => $totalFuelLogs,
            'totalMaintenanceLogs' => $totalMaintenanceLogs,
            'totalFuelCost' => $totalFuelCost,
            'totalMaintCost' => $totalMaintCost,
            'recentActivity' => $recentActivity,
            'maintenanceAlerts' => $maintenanceAlerts,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Listado de usuarios
     */
    public function users(): void
    {
        Auth::requireAdmin();

        $page = (int) $this->get('page', 1);
        $filters = [
            'search' => $this->get('search', ''),
            'role' => $this->get('role', ''),
            'is_active' => $this->get('is_active', '')
        ];

        $users = $this->userModel->getAllUsers($page, 20, $filters);

        $this->render('admin/users/index', [
            'users' => $users,
            'filters' => $filters,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Editar usuario
     */
    public function userEdit(): void
    {
        Auth::requireAdmin();

        $id = (int) $this->get('id');
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->flash('error', 'Usuario no encontrado');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        if ($this->isPost()) {
            $this->processUserEdit($user);
            return;
        }

        $vehicles = $this->userModel->getUserVehicles($id);

        // Stats adicionales para panel de detalle
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM fuel_logs fl JOIN vehicles v ON fl.vehicle_id = v.id WHERE v.user_id = ?) as fuel_count,
                (SELECT COUNT(*) FROM maintenance_logs ml JOIN vehicles v ON ml.vehicle_id = v.id WHERE v.user_id = ?) as maint_count,
                (SELECT COALESCE(SUM(total_cost), 0) FROM fuel_logs fl JOIN vehicles v ON fl.vehicle_id = v.id WHERE v.user_id = ?) as fuel_cost,
                (SELECT COALESCE(SUM(cost), 0) FROM maintenance_logs ml JOIN vehicles v ON ml.vehicle_id = v.id WHERE v.user_id = ?) as maint_cost"
        );
        $stmt->execute([$id, $id, $id, $id]);
        $userStats = $stmt->fetch();

        $this->render('admin/users/edit', [
            'user' => $user,
            'vehicles' => $vehicles,
            'userStats' => $userStats,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar edición de usuario
     */
    private function processUserEdit(array $user): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=admin_user_edit&id=' . $user['id']);
            return;
        }

        $data = [
            'name' => trim($this->post('name', '')),
            'email' => trim($this->post('email', '')),
            'role' => $this->post('role', 'user'),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'email_notifications' => $this->post('email_notifications') ? 1 : 0
        ];

        // Validar
        $validator = new Validator($data);
        $validator
            ->required('name')
            ->required('email')
            ->email('email');

        if ($validator->fails()) {
            $this->flash('error', $validator->firstError());
            $this->redirect('index.php?action=admin_user_edit&id=' . $user['id']);
            return;
        }

        // Verificar email único
        if ($this->userModel->emailExists($data['email'], $user['id'])) {
            $this->flash('error', 'Este email ya está en uso');
            $this->redirect('index.php?action=admin_user_edit&id=' . $user['id']);
            return;
        }

        // Actualizar usuario
        $this->userModel->updateUser($user['id'], $data);

        // Cambiar contraseña si se proporcionó
        $newPassword = $this->post('new_password', '');
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $this->flash('error', 'La contraseña debe tener al menos 6 caracteres');
                $this->redirect('index.php?action=admin_user_edit&id=' . $user['id']);
                return;
            }
            $this->userModel->updatePassword($user['id'], $newPassword);
        }

        // Log de actividad
        $this->activityModel->log('user_edit', "Usuario editado: {$data['email']}", 'user', $user['id']);

        $this->flash('success', 'Usuario actualizado correctamente');
        $this->redirect('index.php?action=admin_users');
    }

    /**
     * Activar/Desactivar usuario
     */
    public function userToggle(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_users');
            return;
        }

        $id = (int) $this->post('id');
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->flash('error', 'Usuario no encontrado');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        // No permitir desactivar al propio admin
        if ($user['id'] === Auth::id()) {
            $this->flash('error', 'No puedes desactivar tu propia cuenta');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        $this->userModel->toggleActive($id);
        $status = $user['is_active'] ? 'desactivado' : 'activado';
        $this->activityModel->log('user_toggle', "Usuario {$status}: {$user['email']}", 'user', $id);

        $this->flash('success', "Usuario {$status} correctamente");
        $this->redirect('index.php?action=admin_users');
    }

    /**
     * Eliminar usuario
     */
    public function userDelete(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_users');
            return;
        }

        $id = (int) $this->post('id');
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->flash('error', 'Usuario no encontrado');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        // No permitir eliminar al propio admin
        if ($user['id'] === Auth::id()) {
            $this->flash('error', 'No puedes eliminar tu propia cuenta');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        $this->userModel->delete($id);
        $this->activityModel->log('user_delete', "Usuario eliminado: {$user['email']}", 'user', $id);

        $this->flash('success', 'Usuario eliminado correctamente');
        $this->redirect('index.php?action=admin_users');
    }

    /**
     * Configuración general
     */
    public function settings(): void
    {
        Auth::requireAdmin();

        if ($this->isPost()) {
            $this->processSettings();
            return;
        }

        $settings = [
            'site_name' => $this->settingModel->get('site_name', 'Vehicle Manager'),
            'site_description' => $this->settingModel->get('site_description', ''),
            'admin_email' => $this->settingModel->get('admin_email', ''),
            'allow_registration' => $this->settingModel->get('allow_registration', true),
            'reminder_days_before' => $this->settingModel->get('reminder_days_before', 7),
            'reminder_enabled' => $this->settingModel->get('reminder_enabled', true),
            'support_enabled' => $this->settingModel->get('support_enabled', true)
        ];

        $this->render('admin/settings/general', [
            'settings' => $settings,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar configuración general
     */
    private function processSettings(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=admin_settings');
            return;
        }

        $this->settingModel->set('site_name', trim($this->post('site_name', '')));
        $this->settingModel->set('site_description', trim($this->post('site_description', '')));
        $this->settingModel->set('admin_email', trim($this->post('admin_email', '')));
        $this->settingModel->set('allow_registration', $this->post('allow_registration') ? '1' : '0', 'boolean');
        $this->settingModel->set('reminder_days_before', (int) $this->post('reminder_days_before', 7), 'integer');
        $this->settingModel->set('reminder_enabled', $this->post('reminder_enabled') ? '1' : '0', 'boolean');
        $this->settingModel->set('support_enabled', $this->post('support_enabled') ? '1' : '0', 'boolean');

        $this->activityModel->log('settings_update', 'Configuración general actualizada');

        $this->flash('success', 'Configuración guardada correctamente');
        $this->redirect('index.php?action=admin_settings');
    }

    /**
     * Configuración de email
     */
    public function email(): void
    {
        Auth::requireAdmin();

        if ($this->isPost()) {
            $this->processEmailSettings();
            return;
        }

        $settings = [
            'mail_driver' => $this->settingModel->get('mail_driver', 'mail'),
            'mail_host' => $this->settingModel->get('mail_host', ''),
            'mail_port' => $this->settingModel->get('mail_port', 587),
            'mail_username' => $this->settingModel->get('mail_username', ''),
            'mail_password' => $this->settingModel->get('mail_password', ''),
            'mail_encryption' => $this->settingModel->get('mail_encryption', 'tls'),
            'mail_from_address' => $this->settingModel->get('mail_from_address', ''),
            'mail_from_name' => $this->settingModel->get('mail_from_name', '')
        ];

        $this->render('admin/settings/email', [
            'settings' => $settings,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar configuración de email
     */
    private function processEmailSettings(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=admin_email');
            return;
        }

        $this->settingModel->set('mail_driver', $this->post('mail_driver', 'mail'));
        $this->settingModel->set('mail_host', trim($this->post('mail_host', '')));
        $this->settingModel->set('mail_port', (int) $this->post('mail_port', 587), 'integer');
        $this->settingModel->set('mail_username', trim($this->post('mail_username', '')));

        // Solo actualizar contraseña si se proporciona
        $password = $this->post('mail_password', '');
        if (!empty($password)) {
            $this->settingModel->set('mail_password', $password);
        }

        $this->settingModel->set('mail_encryption', $this->post('mail_encryption', 'tls'));
        $this->settingModel->set('mail_from_address', trim($this->post('mail_from_address', '')));
        $this->settingModel->set('mail_from_name', trim($this->post('mail_from_name', '')));

        // Limpiar cache de configuración
        $this->settingModel->clearCache();

        $this->activityModel->log('email_settings_update', 'Configuración de email actualizada');

        $this->flash('success', 'Configuración de email guardada correctamente');
        $this->redirect('index.php?action=admin_email');
    }

    /**
     * Probar envío de email
     */
    public function emailTest(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('index.php?action=admin_email');
            return;
        }

        $testEmail = trim($this->post('test_email', ''));

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email de prueba no válido');
            $this->redirect('index.php?action=admin_email');
            return;
        }

        // Forzar recarga de configuración
        $this->settingModel->clearCache();

        $mailer = new Mailer();

        // Probar conexión primero
        $connectionTest = $mailer->testConnection();

        if (!$connectionTest['success']) {
            $this->flash('error', 'Error de conexión: ' . $connectionTest['message']);
            $this->redirect('index.php?action=admin_email');
            return;
        }

        // Enviar email de prueba
        $result = $mailer->sendTemplate('test_email', $testEmail, '', [
            'mail_driver' => $this->settingModel->get('mail_driver'),
            'mail_host' => $this->settingModel->get('mail_host'),
            'mail_port' => $this->settingModel->get('mail_port')
        ]);

        if ($result) {
            $this->activityModel->log('email_test', "Email de prueba enviado a: {$testEmail}");

            if (!empty($connectionTest['details']['warning'])) {
                $this->flash('error', 'Email enviado, pero revisa: ' . $connectionTest['details']['warning']);
            } else {
                $this->flash('success', "Email de prueba enviado correctamente a {$testEmail}");
            }
        } else {
            $this->flash('error', 'Error al enviar: ' . $mailer->getLastError());
        }

        $this->redirect('index.php?action=admin_email');
    }

    /**
     * Plantillas de email
     */
    public function templates(): void
    {
        Auth::requireAdmin();

        $templates = $this->templateModel->all('name', 'ASC');

        $this->render('admin/templates/index', [
            'templates' => $templates,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Editar plantilla de email
     */
    public function templateEdit(): void
    {
        Auth::requireAdmin();

        $id = (int) $this->get('id');
        $template = $this->templateModel->find($id);

        if (!$template) {
            $this->flash('error', 'Plantilla no encontrada');
            $this->redirect('index.php?action=admin_templates');
            return;
        }

        if ($this->isPost()) {
            $this->processTemplateEdit($template);
            return;
        }

        $variables = json_decode($template['variables'], true) ?? [];

        $this->render('admin/templates/edit', [
            'template' => $template,
            'variables' => $variables,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar edición de plantilla
     */
    private function processTemplateEdit(array $template): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=admin_template_edit&id=' . $template['id']);
            return;
        }

        $data = [
            'name' => trim($this->post('name', '')),
            'subject' => trim($this->post('subject', '')),
            'body' => $this->post('body', ''),
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        $this->templateModel->updateTemplate($template['id'], $data);
        $this->activityModel->log('template_edit', "Plantilla editada: {$template['slug']}", 'email_template', $template['id']);

        $this->flash('success', 'Plantilla actualizada correctamente');
        $this->redirect('index.php?action=admin_templates');
    }

    /**
     * Logs de actividad
     */
    public function logs(): void
    {
        Auth::requireAdmin();

        $page = (int) $this->get('page', 1);
        $filters = [
            'user_id' => $this->get('user_id', ''),
            'action' => $this->get('action_filter', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', '')
        ];

        $logs = $this->activityModel->getFiltered($filters, $page, 30);
        $actions = $this->activityModel->getUniqueActions();

        $db = Database::getInstance();
        $users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

        $this->render('admin/logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
            'users' => $users,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Limpiar logs
     */
    public function logsClear(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_logs');
            return;
        }

        $days = (int) $this->post('days', 90);
        $deleted = $this->activityModel->cleanOldLogs($days);

        $message = $days <= 0
            ? "Se eliminaron todos los {$deleted} registros de logs"
            : "Se eliminaron {$deleted} registros de más de {$days} días";

        $this->activityModel->log('logs_clear', $message);

        $this->flash('success', $message);
        $this->redirect('index.php?action=admin_logs');
    }

    /**
     * Estadísticas de logs
     */
    public function logStats(): void
    {
        Auth::requireAdmin();

        $stats = $this->activityModel->getStats();

        $this->render('admin/logs/stats', [
            'stats' => $stats
        ]);
    }

    /**
     * Exportar logs a CSV
     */
    public function logExport(): void
    {
        Auth::requireAdmin();

        $filters = [
            'user_id' => $this->get('user_id', ''),
            'action' => $this->get('action_filter', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', '')
        ];

        $logs = $this->activityModel->getFiltered($filters, 1, 50000);

        $filename = 'logs_actividad_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['Fecha', 'Usuario', 'Email', 'Acción', 'Descripción', 'Entidad', 'ID', 'IP', 'User Agent'], ';');

        foreach ($logs['data'] as $log) {
            fputcsv($output, [
                date('d/m/Y H:i:s', strtotime($log['created_at'])),
                $log['user_name'] ?? 'Sistema',
                $log['user_email'] ?? '',
                $log['action'],
                $log['description'],
                $log['entity_type'] ?? '',
                $log['entity_id'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Impersonar usuario (admin se conecta como ese usuario)
     */
    public function impersonate(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_users');
            return;
        }

        $id = (int) $this->post('id');
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->flash('error', 'Usuario no encontrado');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        if ($user['role'] === 'admin') {
            $this->flash('error', 'No se puede impersonar a otro administrador');
            $this->redirect('index.php?action=admin_users');
            return;
        }

        // Guardar sesión del admin actual
        $_SESSION['impersonating_admin'] = [
            'id' => Auth::id(),
            'name' => Auth::name(),
            'email' => Auth::email(),
            'role' => Auth::role(),
            'theme' => Auth::theme()
        ];

        $this->activityModel->log('admin_impersonate', "Admin impersonó al usuario: {$user['email']}", 'user', $user['id']);

        // Cargar sesión del usuario objetivo
        Auth::login($user);

        $this->redirect('index.php?action=dashboard');
    }

    /**
     * Finalizar impersonación y volver a sesión admin
     */
    public function impersonateEnd(): void
    {
        if (!isset($_SESSION['impersonating_admin'])) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $admin = $_SESSION['impersonating_admin'];
        unset($_SESSION['impersonating_admin']);

        Auth::login($admin);

        $this->flash('success', 'Sesión de impersonación finalizada');
        $this->redirect('index.php?action=admin_users');
    }

    /**
     * Lista de tickets (admin)
     */
    public function tickets(): void
    {
        Auth::requireAdmin();

        $status = $this->get('status', '');
        $page = (int) $this->get('page', 1);

        $ticketModel = new Ticket();
        $tickets = $ticketModel->getAllTickets($status, $page);
        $openCount = $ticketModel->countOpen();

        $this->render('admin/tickets/index', [
            'tickets' => $tickets,
            'status' => $status,
            'openCount' => $openCount,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Detalle de un ticket (admin)
     */
    public function ticketDetail(): void
    {
        Auth::requireAdmin();

        $id = (int) $this->get('id');
        $ticketModel = new Ticket();
        $ticket = $ticketModel->find($id);

        if (!$ticket) {
            $this->flash('error', 'Ticket no encontrado');
            $this->redirect('index.php?action=admin_tickets');
            return;
        }

        $user = $this->userModel->find($ticket['user_id']);

        // Añadir vehicle_count al user para el sidebar
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ?");
        $stmt->execute([$ticket['user_id']]);
        $user['vehicle_count'] = (int) $stmt->fetchColumn();

        $this->render('admin/tickets/detail', [
            'ticket' => $ticket,
            'user' => $user,
            'csrf_token' => $this->generateCsrf(),
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Responder a un ticket
     */
    public function ticketReply(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_tickets');
            return;
        }

        $id = (int) $this->post('id');
        $ticketModel = new Ticket();
        $ticket = $ticketModel->find($id);

        if (!$ticket) {
            $this->flash('error', 'Ticket no encontrado');
            $this->redirect('index.php?action=admin_tickets');
            return;
        }

        $reply = trim($this->post('reply', ''));
        $close = $this->post('close') ? true : false;

        if (empty($reply)) {
            $this->flash('error', 'El mensaje de respuesta es obligatorio');
            $this->redirect('index.php?action=admin_ticket_detail&id=' . $id);
            return;
        }

        $ticketModel->reply($id, $reply, $close);
        $this->activityModel->log('ticket_reply', "Respuesta a ticket #{$id}", 'ticket', $id);

        $this->flash('success', $close ? 'Respuesta enviada y ticket cerrado' : 'Respuesta enviada');
        $this->redirect('index.php?action=admin_ticket_detail&id=' . $id);
    }

    /**
     * Abrir/Cerrar ticket
     */
    public function ticketClose(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=admin_tickets');
            return;
        }

        $id = (int) $this->post('id');
        $ticketModel = new Ticket();
        $ticket = $ticketModel->find($id);

        if (!$ticket) {
            $this->flash('error', 'Ticket no encontrado');
            $this->redirect('index.php?action=admin_tickets');
            return;
        }

        $newStatus = $ticket['status'] === 'open' ? 'closed' : 'open';
        $ticketModel->setStatus($id, $newStatus);

        $label = $newStatus === 'closed' ? 'cerrado' : 'reabierto';
        $this->activityModel->log('ticket_status', "Ticket #{$id} {$label}", 'ticket', $id);

        $this->flash('success', "Ticket {$label} correctamente");
        $this->redirect('index.php?action=admin_ticket_detail&id=' . $id);
    }

    /**
     * Panel de salud del sistema
     */
    public function health(): void
    {
        Auth::requireAdmin();

        $db = Database::getInstance();
        $config = require __DIR__ . '/../config/app.php';

        // Versiones
        $phpVersion = phpversion();
        $mysqlVersion = $db->query("SELECT VERSION()")->fetchColumn();

        // Disco
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }
        $diskFree = disk_free_space($uploadsDir);
        $diskTotal = disk_total_space($uploadsDir);

        // Cron
        $cronLastRun = $this->settingModel->get('cron_last_run', null);
        $cronUrl = ($config['url'] ?? '') . '/index.php?action=cron_queue&token=' . ($config['cron']['token'] ?? '');

        // Cola de emails
        $queueModel = new EmailQueue();
        $rawStats = $queueModel->getStats();
        $queueStats = ['pending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rawStats as $row) {
            $queueStats[$row['status']] = (int) $row['count'];
        }

        // Tickets abiertos
        $ticketModel = new Ticket();
        $openTickets = $ticketModel->countOpen();

        $this->render('admin/health', [
            'phpVersion' => $phpVersion,
            'mysqlVersion' => $mysqlVersion,
            'diskFree' => $diskFree,
            'diskTotal' => $diskTotal,
            'cronLastRun' => $cronLastRun,
            'cronUrl' => $cronUrl,
            'queueStats' => $queueStats,
            'openTickets' => $openTickets
        ]);
    }
}
