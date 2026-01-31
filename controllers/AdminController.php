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

        $this->render('admin/dashboard', [
            'userStats' => $userStats,
            'totalVehicles' => $totalVehicles,
            'totalFuelLogs' => $totalFuelLogs,
            'totalMaintenanceLogs' => $totalMaintenanceLogs,
            'totalFuelCost' => $totalFuelCost,
            'totalMaintCost' => $totalMaintCost,
            'recentActivity' => $recentActivity,
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

        $this->render('admin/users/edit', [
            'user' => $user,
            'vehicles' => $vehicles,
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
            'reminder_enabled' => $this->settingModel->get('reminder_enabled', true)
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
            $this->flash('success', "Email de prueba enviado correctamente a {$testEmail}");
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
            'action' => $this->get('action', ''),
            'date_from' => $this->get('date_from', ''),
            'date_to' => $this->get('date_to', '')
        ];

        $logs = $this->activityModel->getFiltered($filters, $page, 30);
        $actions = $this->activityModel->getUniqueActions();

        $this->render('admin/logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions
        ]);
    }

    /**
     * Limpiar logs antiguos
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

        $this->activityModel->log('logs_clear', "Logs antiguos eliminados: {$deleted} registros");

        $this->flash('success', "Se eliminaron {$deleted} registros de más de {$days} días");
        $this->redirect('index.php?action=admin_logs');
    }
}
