<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-gear me-2"></i>Configuración General</h1>
            <a href="index.php?action=admin_dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver al Panel
            </a>
        </div>

        <!-- Navegación de configuración -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="index.php?action=admin_settings">
                    <i class="bi bi-gear me-1"></i>General
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?action=admin_email">
                    <i class="bi bi-envelope me-1"></i>Email
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?action=admin_templates">
                    <i class="bi bi-file-earmark-text me-1"></i>Plantillas
                </a>
            </li>
        </ul>

        <form method="POST" action="index.php?action=admin_settings">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Información del Sitio</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Sitio</label>
                        <input type="text" name="site_name" class="form-control"
                               value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                        <div class="form-text">Se mostrará en el título y cabecera</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="site_description" class="form-control" rows="2"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                        <div class="form-text">Descripción corta del sitio (opcional)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email del Administrador</label>
                        <input type="email" name="admin_email" class="form-control"
                               value="<?= htmlspecialchars($settings['admin_email']) ?>">
                        <div class="form-text">Email para recibir notificaciones del sistema</div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registro de Usuarios</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_registration"
                               id="allow_registration" value="1" <?= $settings['allow_registration'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="allow_registration">Permitir Registro Público</label>
                    </div>
                    <div class="form-text">Si está desactivado, solo un administrador puede crear usuarios</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Recordatorios de Mantenimiento</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reminder_enabled"
                                   id="reminder_enabled" value="1" <?= $settings['reminder_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="reminder_enabled">Enviar Recordatorios por Email</label>
                        </div>
                        <div class="form-text">Enviar emails automáticos antes del vencimiento</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Días de Antelación</label>
                        <input type="number" name="reminder_days_before" class="form-control"
                               value="<?= (int) $settings['reminder_days_before'] ?>" min="1" max="30" style="max-width: 150px;">
                        <div class="form-text">Días antes del vencimiento para enviar el recordatorio</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?action=admin_dashboard" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>
