<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-person-gear me-2"></i>Editar Usuario</h1>
            <div class="d-flex gap-2">
                <?php if ($user['role'] !== 'admin'): ?>
                <form method="POST" action="index.php?action=admin_impersonate" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-person-lines-fill me-1"></i>Conectar como este usuario
                    </button>
                </form>
                <?php endif; ?>
                <a href="index.php?action=admin_users" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información del Usuario</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?action=admin_user_edit&id=<?= $user['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Dejar vacío para mantener la actual">
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Cuenta Activa</label>
                            </div>
                            <div class="form-text">Los usuarios inactivos no pueden iniciar sesión</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications"
                                       id="email_notifications" value="1" <?= !empty($user['email_notifications']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="email_notifications">Notificaciones por Email</label>
                            </div>
                            <div class="form-text">Recibir recordatorios de mantenimiento</div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=admin_users" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">ID:</td>
                                <td><strong>#<?= $user['id'] ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Registrado:</td>
                                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Último acceso:</td>
                                <td>
                                    <?php if (!empty($user['last_login'])): ?>
                                        <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Repostajes:</td>
                                <td><strong><?= number_format($userStats['fuel_count'] ?? 0) ?></strong>
                                    <span class="text-muted small">(<?= number_format($userStats['fuel_cost'] ?? 0, 2) ?> €)</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mantenimientos:</td>
                                <td><strong><?= number_format($userStats['maint_count'] ?? 0) ?></strong>
                                    <span class="text-muted small">(<?= number_format($userStats['maint_cost'] ?? 0, 2) ?> €)</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gasto total:</td>
                                <td><strong class="text-success"><?= number_format(($userStats['fuel_cost'] ?? 0) + ($userStats['maint_cost'] ?? 0), 2) ?> €</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-car-front me-2"></i>Vehículos (<?= count($vehicles) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vehicles)): ?>
                            <p class="text-muted mb-0">Sin vehículos registrados</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>
                                            <strong><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($vehicle['plate']) ?></small>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <?= number_format($vehicle['current_km']) ?> km
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
