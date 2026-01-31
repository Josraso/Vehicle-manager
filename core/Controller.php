<?php
/**
 * Clase Controller Base
 */

abstract class Controller
{
    protected array $data = [];

    /**
     * Renderizar vista
     */
    protected function render(string $view, array $data = []): void
    {
        // Combinar datos
        $this->data = array_merge($this->data, $data);

        // Extraer variables para la vista
        extract($this->data);

        // Ruta de la vista
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception("Vista no encontrada: {$view}");
        }

        // Capturar contenido de la vista
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Renderizar con layout
        require __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * Renderizar vista sin layout (para login/register)
     */
    protected function renderWithoutLayout(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../views/' . $view . '.php';
    }

    /**
     * Redireccionar
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Obtener datos POST
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtener datos GET
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Verificar si es POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Establecer mensaje flash
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtener y limpiar mensaje flash
     */
    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Respuesta JSON
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Validar CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->post('csrf_token');
        return $token && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generar CSRF token
     */
    protected function generateCsrf(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
