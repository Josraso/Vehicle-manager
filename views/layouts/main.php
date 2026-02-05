<!DOCTYPE html>
<html lang="es" data-bs-theme="<?= Auth::theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Vehicle Manager' ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0D6EFD">
</head>
<body>
    <!-- Banner de impersonación -->
    <?php if (isset($_SESSION['impersonating_admin'])): ?>
    <div class="alert alert-warning text-center py-2 m-0" role="alert" style="border-radius:0;">
        <i class="bi bi-person-lines-fill me-2"></i>
        Estás viendo la aplicación como <strong><?= htmlspecialchars(Auth::name()) ?></strong>
        <a href="index.php?action=admin_impersonate_end" class="btn btn-sm btn-outline-dark ms-3">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Volver a Admin
        </a>
    </div>
    <?php endif; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top bg-body-tertiary border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php?action=dashboard">
                <i class="bi bi-speedometer2 me-2"></i>
                <span class="fw-bold">Vehicle Manager</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?action=dashboard">
                            <i class="bi bi-grid-fill me-1"></i> Mis Vehículos
                        </a>
                    </li>
                    <?php if (Auth::isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-danger" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shield-lock me-1"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_dashboard">
                                    <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_users">
                                    <i class="bi bi-people me-2"></i>Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_settings">
                                    <i class="bi bi-gear me-2"></i>Configuración
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_email">
                                    <i class="bi bi-envelope me-2"></i>Email
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_templates">
                                    <i class="bi bi-file-earmark-text me-2"></i>Plantillas
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_tickets">
                                    <i class="bi bi-headset me-2"></i>Tickets
                                    <?php
                                    $openTicketCount = (new Ticket())->countOpen();
                                    if ($openTicketCount > 0):
                                    ?>
                                        <span class="badge bg-danger ms-auto"><?= $openTicketCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_logs">
                                    <i class="bi bi-clock-history me-2"></i>Logs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="index.php?action=admin_health">
                                    <i class="bi bi-activity me-2"></i>Salud del Sistema
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if ((new Setting())->get('support_enabled', true)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?action=tickets">
                            <i class="bi bi-headset me-1"></i> Soporte
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <!-- PWA Install -->
                    <li class="nav-item d-none" id="pwaInstallItem">
                        <button class="btn btn-link nav-link" id="pwaInstallBtn" title="Instalar en móvil">
                            <i class="bi bi-phone-download"></i>
                        </button>
                    </li>

                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <button class="btn btn-link nav-link" id="themeToggle" title="Cambiar tema">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                        </button>
                    </li>

                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars(Auth::name()) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="index.php?action=profile">
                                    <i class="bi bi-person me-2"></i> Mi Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="index.php?action=logout">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-body-tertiary border-top py-3 mt-auto">
        <div class="container text-center text-muted">
            <small>&copy; <?= date('Y') ?> Vehicle Manager - Control de Vehículos</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>

    <!-- PWA Service Worker -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    }
    </script>
</body>
</html>
