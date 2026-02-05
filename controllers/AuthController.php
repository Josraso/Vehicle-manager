<?php
/**
 * Controller de Autenticación
 */

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Mostrar formulario de login
     */
    public function login(): void
    {
        Auth::guest();

        if ($this->isPost()) {
            $this->processLogin();
            return;
        }

        $this->renderWithoutLayout('auth/login', [
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar login
     */
    private function processLogin(): void
    {
        if (!$this->validateCsrf()) {
            $this->renderWithoutLayout('auth/login', [
                'error' => 'Token de seguridad inválido',
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');

        $validator = new Validator(['email' => $email, 'password' => $password]);
        $validator->required('email')->email('email')->required('password');

        if ($validator->fails()) {
            $this->renderWithoutLayout('auth/login', [
                'error' => $validator->firstError(),
                'email' => $email,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->renderWithoutLayout('auth/login', [
                'error' => 'Email o contraseña incorrectos',
                'email' => $email,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Verificar si el usuario está activo
        if (isset($user['is_active']) && !$user['is_active']) {
            $this->renderWithoutLayout('auth/login', [
                'error' => 'Tu cuenta está desactivada. Contacta al administrador.',
                'email' => $email,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        Auth::login($user);

        // Actualizar último login
        $this->userModel->updateLastLogin($user['id']);

        // Recuerdo de usuario: extiende la sesión 30 días
        if ($this->post('remember_me')) {
            $_SESSION['remember_me'] = true;
            $_SESSION['session_expires'] = time() + (30 * 24 * 60 * 60);
            setcookie(session_name(), session_id(), [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            $_SESSION['session_expires'] = time() + 7200;
        }

        $this->redirect('index.php?action=dashboard');
    }

    /**
     * Mostrar formulario de registro
     */
    public function register(): void
    {
        Auth::guest();

        if ($this->isPost()) {
            $this->processRegister();
            return;
        }

        $this->renderWithoutLayout('auth/register', [
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar registro
     */
    private function processRegister(): void
    {
        if (!$this->validateCsrf()) {
            $this->renderWithoutLayout('auth/register', [
                'error' => 'Token de seguridad inválido',
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $data = [
            'name' => trim($this->post('name', '')),
            'email' => trim($this->post('email', '')),
            'password' => $this->post('password', ''),
            'password_confirm' => $this->post('password_confirm', '')
        ];

        $validator = new Validator($data);
        $validator
            ->required('name', 'El nombre es obligatorio')
            ->min('name', 2, 'El nombre debe tener al menos 2 caracteres')
            ->required('email', 'El email es obligatorio')
            ->email('email', 'El email no es válido')
            ->required('password', 'La contraseña es obligatoria')
            ->min('password', 6, 'La contraseña debe tener al menos 6 caracteres')
            ->matches('password', 'password_confirm', 'Las contraseñas no coinciden');

        if ($validator->fails()) {
            $this->renderWithoutLayout('auth/register', [
                'error' => $validator->firstError(),
                'name' => $data['name'],
                'email' => $data['email'],
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        if ($this->userModel->emailExists($data['email'])) {
            $this->renderWithoutLayout('auth/register', [
                'error' => 'Este email ya está registrado',
                'name' => $data['name'],
                'email' => $data['email'],
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $userId = $this->userModel->createUser($data['name'], $data['email'], $data['password']);

        if ($userId) {
            $config = require __DIR__ . '/../config/app.php';
            $mailer = new Mailer();
            $mailer->sendTemplate('welcome', $data['email'], $data['name'], [
                'user_name' => $data['name'],
                'user_email' => $data['email'],
                'site_name' => $config['app_name'] ?? 'Vehicle Manager',
                'site_url' => $config['url'] ?? ''
            ]);

            $this->flash('success', 'Cuenta creada correctamente. Inicia sesión.');
            $this->redirect('index.php?action=login');
        } else {
            $this->renderWithoutLayout('auth/register', [
                'error' => 'Error al crear la cuenta',
                'csrf_token' => $this->generateCsrf()
            ]);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('index.php?action=login');
    }

    /**
     * Mostrar formulario de recuperar contraseña
     */
    public function forgotPassword(): void
    {
        Auth::guest();

        if ($this->isPost()) {
            $this->processForgotPassword();
            return;
        }

        $this->renderWithoutLayout('auth/forgot_password', [
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar solicitud de recuperación
     */
    private function processForgotPassword(): void
    {
        $email = trim($this->post('email', ''));

        $user = $this->userModel->findByEmail($email);

        if ($user) {
            $token = $this->userModel->generateResetToken($user['id']);
            $config = require __DIR__ . '/../config/app.php';
            $resetLink = $config['url'] . "/index.php?action=reset_password&token={$token}";

            $mailer = new Mailer();
            $mailer->sendTemplate('password_reset', $user['email'], $user['name'], [
                'user_name' => $user['name'],
                'reset_link' => $resetLink
            ]);
        }

        // Mismo mensaje si existe o no (no revelar qué emails están registrados)
        $this->renderWithoutLayout('auth/forgot_password', [
            'success' => true,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Restablecer contraseña
     */
    public function resetPassword(): void
    {
        Auth::guest();

        $token = $this->get('token', '');
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            $this->flash('error', 'El enlace ha expirado o no es válido');
            $this->redirect('index.php?action=login');
            return;
        }

        if ($this->isPost()) {
            $this->processResetPassword($user);
            return;
        }

        $this->renderWithoutLayout('auth/reset_password', [
            'token' => $token,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Procesar restablecimiento
     */
    private function processResetPassword(array $user): void
    {
        $password = $this->post('password', '');
        $passwordConfirm = $this->post('password_confirm', '');

        $validator = new Validator([
            'password' => $password,
            'password_confirm' => $passwordConfirm
        ]);

        $validator
            ->required('password')
            ->min('password', 6)
            ->matches('password', 'password_confirm', 'Las contraseñas no coinciden');

        if ($validator->fails()) {
            $this->renderWithoutLayout('auth/reset_password', [
                'error' => $validator->firstError(),
                'token' => $this->post('token'),
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $this->userModel->updatePassword($user['id'], $password);
        $this->userModel->clearResetToken($user['id']);

        $this->flash('success', 'Contraseña actualizada correctamente');
        $this->redirect('index.php?action=login');
    }
}
