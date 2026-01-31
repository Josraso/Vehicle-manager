<?php
/**
 * Controller de Perfil de Usuario
 */

class ProfileController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Ver/Editar perfil
     */
    public function index(): void
    {
        Auth::require();

        $user = $this->userModel->find(Auth::id());

        if ($this->isPost()) {
            $this->update($user);
            return;
        }

        $this->render('profile/index', [
            'user' => $user,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Actualizar perfil
     */
    private function update(array $user): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=profile');
            return;
        }

        $action = $this->post('form_action', 'profile');

        switch ($action) {
            case 'profile':
                $this->updateProfile($user);
                break;
            case 'password':
                $this->updatePassword($user);
                break;
            case 'theme':
                $this->updateTheme($user);
                break;
            case 'preferences':
                $this->updatePreferences($user);
                break;
        }
    }

    /**
     * Actualizar datos del perfil
     */
    private function updateProfile(array $user): void
    {
        $data = [
            'name' => trim($this->post('name', '')),
            'email' => trim($this->post('email', ''))
        ];

        $validator = new Validator($data);
        $validator
            ->required('name', 'El nombre es obligatorio')
            ->min('name', 2, 'El nombre debe tener al menos 2 caracteres')
            ->required('email', 'El email es obligatorio')
            ->email('email', 'El email no es válido');

        if ($validator->fails()) {
            $this->render('profile/index', [
                'user' => array_merge($user, $data),
                'error' => $validator->firstError(),
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Verificar que el email no esté en uso por otro usuario
        if ($this->userModel->emailExists($data['email'], $user['id'])) {
            $this->render('profile/index', [
                'user' => array_merge($user, $data),
                'error' => 'Este email ya está en uso',
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $this->userModel->update($user['id'], $data);

        // Actualizar nombre en sesión
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];

        $this->flash('success', 'Perfil actualizado correctamente');
        $this->redirect('index.php?action=profile');
    }

    /**
     * Actualizar contraseña
     */
    private function updatePassword(array $user): void
    {
        $currentPassword = $this->post('current_password', '');
        $newPassword = $this->post('new_password', '');
        $confirmPassword = $this->post('confirm_password', '');

        // Verificar contraseña actual
        if (!$this->userModel->verifyPassword($currentPassword, $user['password'])) {
            $this->render('profile/index', [
                'user' => $user,
                'password_error' => 'La contraseña actual es incorrecta',
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $validator = new Validator([
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword
        ]);

        $validator
            ->required('new_password', 'La nueva contraseña es obligatoria')
            ->min('new_password', 6, 'La contraseña debe tener al menos 6 caracteres')
            ->matches('new_password', 'confirm_password', 'Las contraseñas no coinciden');

        if ($validator->fails()) {
            $this->render('profile/index', [
                'user' => $user,
                'password_error' => $validator->firstError(),
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $this->userModel->updatePassword($user['id'], $newPassword);

        $this->flash('success', 'Contraseña actualizada correctamente');
        $this->redirect('index.php?action=profile');
    }

    /**
     * Cambiar tema
     */
    private function updateTheme(array $user): void
    {
        $theme = $this->post('theme', 'light');
        $theme = in_array($theme, ['light', 'dark']) ? $theme : 'light';

        $this->userModel->updateTheme($user['id'], $theme);
        Auth::setTheme($theme);

        $this->flash('success', 'Tema actualizado');
        $this->redirect('index.php?action=profile');
    }

    /**
     * Actualizar preferencias (tema + notificaciones)
     */
    private function updatePreferences(array $user): void
    {
        $theme = $this->post('theme', 'light');
        $theme = in_array($theme, ['light', 'dark']) ? $theme : 'light';
        $emailNotifications = $this->post('email_notifications') ? 1 : 0;

        $this->userModel->update($user['id'], [
            'theme' => $theme,
            'email_notifications' => $emailNotifications
        ]);

        Auth::setTheme($theme);

        $this->flash('success', 'Preferencias actualizadas correctamente');
        $this->redirect('index.php?action=profile');
    }

    /**
     * Cambiar tema via AJAX
     */
    public function toggleTheme(): void
    {
        Auth::require();

        $user = $this->userModel->find(Auth::id());
        $newTheme = $user['theme'] === 'light' ? 'dark' : 'light';

        $this->userModel->updateTheme($user['id'], $newTheme);
        Auth::setTheme($newTheme);

        $this->json(['success' => true, 'theme' => $newTheme]);
    }
}
