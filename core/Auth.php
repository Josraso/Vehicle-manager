<?php
/**
 * Clase Auth - Gestión de autenticación y sesiones
 */

class Auth
{
    /**
     * Iniciar sesión
     */
    public static function login(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['user_theme'] = $user['theme'] ?? 'light';
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Cerrar sesión
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtener nombre del usuario actual
     */
    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Obtener email del usuario actual
     */
    public static function email(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    /**
     * Obtener rol del usuario actual
     */
    public static function role(): string
    {
        return $_SESSION['user_role'] ?? 'user';
    }

    /**
     * Verificar si es admin
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Obtener tema del usuario
     */
    public static function theme(): string
    {
        return $_SESSION['user_theme'] ?? 'light';
    }

    /**
     * Cambiar tema
     */
    public static function setTheme(string $theme): void
    {
        $_SESSION['user_theme'] = $theme;
    }

    /**
     * Requerir autenticación o redirigir
     */
    public static function require(): void
    {
        if (!self::check()) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    /**
     * Requerir rol de admin
     */
    public static function requireAdmin(): void
    {
        self::require();

        if (!self::isAdmin()) {
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Redirigir si ya está autenticado
     */
    public static function guest(): void
    {
        if (self::check()) {
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Obtener datos del usuario actual
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => self::id(),
            'name' => self::name(),
            'email' => self::email(),
            'role' => self::role(),
            'theme' => self::theme()
        ];
    }
}
