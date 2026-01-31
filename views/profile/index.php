<?php $title = 'Mi Perfil - Vehicle Manager'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-4">
            <i class="bi bi-person-circle me-2"></i>Mi Perfil
        </h1>

        <!-- Datos del perfil -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Datos Personales</h6>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=profile">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="form_action" value="profile">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <?php if (isset($password_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($password_error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=profile">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="form_action" value="password">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                   minlength="6" required>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-1"></i> Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>

        <!-- Preferencias -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Preferencias</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?action=profile">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="form_action" value="preferences">

                    <div class="mb-4">
                        <label class="form-label">Tema de la Aplicación</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="theme" id="themeLight" value="light"
                                   <?= ($user['theme'] ?? 'light') === 'light' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="themeLight">
                                <i class="bi bi-sun me-1"></i> Claro
                            </label>

                            <input type="radio" class="btn-check" name="theme" id="themeDark" value="dark"
                                   <?= ($user['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="themeDark">
                                <i class="bi bi-moon me-1"></i> Oscuro
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications"
                                   id="email_notifications" value="1"
                                   <?= !empty($user['email_notifications']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notifications">
                                <i class="bi bi-bell me-1"></i> Notificaciones por Email
                            </label>
                        </div>
                        <div class="form-text">
                            Recibe recordatorios de mantenimiento y otras notificaciones importantes por correo electrónico.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-check-lg me-1"></i> Guardar Preferencias
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
