<?php
/**
 * Vehicle Manager - Instalador
 *
 * Accesible solo cuando la aplicación no ha sido instalada.
 * Tras la instalación crea el archivo .installed y se bloquea el acceso.
 */

// Si ya está instalada, redirigir
if (file_exists(__DIR__ . '/.installed')) {
    header('Location: index.php?action=login');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appUrl      = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $dbHost      = trim($_POST['db_host'] ?? 'localhost');
    $dbName      = trim($_POST['db_name'] ?? '');
    $dbUser      = trim($_POST['db_user'] ?? '');
    $dbPass      = $_POST['db_pass'] ?? '';
    $adminName   = trim($_POST['admin_name'] ?? '');
    $adminEmail  = trim($_POST['admin_email'] ?? '');
    $adminPass   = $_POST['admin_pass'] ?? '';
    $adminPassC  = $_POST['admin_pass_confirm'] ?? '';

    // Validaciones
    if (empty($appUrl) || empty($dbName) || empty($dbUser) || empty($adminName) || empty($adminEmail) || empty($adminPass)) {
        $error = 'Todos los campos obligatorios deben estar rellenados.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email del administrador no es válido.';
    } elseif ($adminPass !== $adminPassC) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($adminPass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        $error = 'El nombre de la base de datos solo puede contener letras, números y guiones bajos.';
    } else {
        try {
            // Conectar sin seleccionar base de datos
            $pdo = new PDO(
                "mysql:host={$dbHost};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Crear base de datos si no existe
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Leer database.sql
            $sql = file_get_contents(__DIR__ . '/database.sql');

            // Eliminar CREATE DATABASE y USE (ya los hicimos arriba)
            $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sql = preg_replace('/^USE [^;]+;/im', '', $sql);

            // Eliminar los INSERT de usuarios por defecto (los reemplazamos por el admin que el usuario configura)
            $sql = preg_replace('/-- =+\s*\n-- USUARIO ADMINISTRADOR[\s\S]*$/m', '', $sql);

            // Ejecutar esquema
            $pdo->exec($sql);

            // Crear usuario administrador
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
                ->execute([$adminName, $adminEmail, $hash]);

            // Escribir config/database.php
            $dbConfigContent = "<?php\n/**\n * Configuración de Base de Datos\n */\n\nreturn [\n"
                . "    'host' => " . var_export($dbHost, true) . ",\n"
                . "    'database' => " . var_export($dbName, true) . ",\n"
                . "    'username' => " . var_export($dbUser, true) . ",\n"
                . "    'password' => " . var_export($dbPass, true) . ",\n"
                . "    'charset' => 'utf8mb4',\n"
                . "    'options' => [\n"
                . "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n"
                . "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
                . "        PDO::ATTR_EMULATE_PREPARES => false,\n"
                . "    ]\n"
                . "];\n";
            file_put_contents(__DIR__ . '/config/database.php', $dbConfigContent);

            // Actualizar URL en config/app.php
            $appConfigPath = __DIR__ . '/config/app.php';
            $appConfig = file_get_contents($appConfigPath);
            $appConfig = preg_replace(
                "/'url'\s*=>\s*'[^']*'/",
                "'url' => " . var_export($appUrl, true),
                $appConfig
            );
            file_put_contents($appConfigPath, $appConfig);

            // Crear flag de instalación
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s') . "\n");

            $success = true;

        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Vehicle Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-body-secondary">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">

                <div class="text-center mb-4">
                    <i class="bi bi-speedometer2 display-1 text-primary"></i>
                    <h1 class="h3 mt-3">Vehicle Manager</h1>
                    <p class="text-muted">Instalación inicial</p>
                </div>

                <?php if ($success): ?>
                <!-- Instalación completada -->
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">¡Instalación completada!</h4>
                        <p class="text-muted">La aplicación está lista para usar.</p>
                        <a href="index.php?action=login" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ir al Login
                        </a>
                    </div>
                </div>

                <?php else: ?>
                <!-- Formulario de instalación -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Configuración</h5>
                    </div>
                    <div class="card-body p-4">

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-sm">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="install.php">

                            <!-- URL de la aplicación -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">URL de la aplicación</label>
                                <input type="text" name="app_url" class="form-control"
                                       value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost/Vehicle-manager') ?>"
                                       placeholder="http://tudominio.com/Vehicle-manager">
                                <div class="form-text">URL base desde donde se accede a la aplicación (sin barra final).</div>
                            </div>

                            <hr>

                            <!-- Base de datos -->
                            <div class="mb-2">
                                <h6 class="fw-semibold"><i class="bi bi-database me-1"></i>Base de datos</h6>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Servidor</label>
                                    <input type="text" name="db_host" class="form-control"
                                           value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
                                           placeholder="localhost">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nombre de la BD</label>
                                    <input type="text" name="db_name" class="form-control"
                                           value="<?= htmlspecialchars($_POST['db_name'] ?? 'vehicle_manager') ?>"
                                           placeholder="vehicle_manager">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Usuario</label>
                                    <input type="text" name="db_user" class="form-control"
                                           value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"
                                           placeholder="root">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contraseña</label>
                                    <input type="password" name="db_pass" class="form-control"
                                           placeholder="(dejar vacío si no tiene)">
                                </div>
                            </div>

                            <hr>

                            <!-- Administrador -->
                            <div class="mb-2">
                                <h6 class="fw-semibold"><i class="bi bi-person-fill me-1"></i>Cuenta del administrador</h6>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="admin_name" class="form-control"
                                           value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>"
                                           placeholder="Administrador" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="admin_email" class="form-control"
                                           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>"
                                           placeholder="admin@tudominio.com" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contraseña</label>
                                    <input type="password" name="admin_pass" class="form-control"
                                           placeholder="Mínimo 6 caracteres" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmar contraseña</label>
                                    <input type="password" name="admin_pass_confirm" class="form-control"
                                           placeholder="Repite la contraseña" required>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-play-circle me-2"></i>Instalar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
