<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Vehicle Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0D6EFD">
</head>
<body class="bg-body-secondary">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <i class="bi bi-speedometer2 display-1 text-primary"></i>
                    <h1 class="h3 mt-3">Vehicle Manager</h1>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title h5 text-center mb-4">Nueva Contraseña</h2>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-sm">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php?action=reset_password&token=<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           minlength="6" required autofocus>
                                </div>
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password_confirm"
                                           name="password_confirm" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
