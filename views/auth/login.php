<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Vehicle Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-body-secondary">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <i class="bi bi-speedometer2 display-1 text-primary"></i>
                    <h1 class="h3 mt-3">Vehicle Manager</h1>
                    <p class="text-muted">Control de vehículos</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title h5 text-center mb-4">Iniciar Sesión</h2>

                        <?php if (isset($_SESSION['flash'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger' ?> alert-sm">
                                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                            </div>
                            <?php unset($_SESSION['flash']); ?>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-sm">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php?action=login">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($email ?? '') ?>" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me">
                                <label class="form-check-label small" for="remember_me">Recuerdo mi usuario</label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="index.php?action=forgot_password" class="text-decoration-none small">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <span class="text-muted">¿No tienes cuenta?</span>
                    <a href="index.php?action=register" class="text-decoration-none ms-1">Regístrate</a>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
