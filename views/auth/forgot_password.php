<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Vehicle Manager</title>
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
                        <h2 class="card-title h5 text-center mb-4">Recuperar Contraseña</h2>

                        <?php if (isset($success) && $success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Si el email existe, recibirás instrucciones para restablecer tu contraseña.
                            </div>

                            <div class="text-center">
                                <a href="index.php?action=login" class="btn btn-outline-primary">
                                    Volver al Login
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small text-center mb-4">
                                Introduce tu email y te enviaremos instrucciones para restablecer tu contraseña.
                            </p>

                            <form method="POST" action="index.php?action=forgot_password">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                                <div class="mb-4">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Enviar
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php?action=login" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Volver al login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
