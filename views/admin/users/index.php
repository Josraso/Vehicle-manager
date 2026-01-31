<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-people me-2"></i>Gestión de Usuarios</h1>
    <a href="index.php?action=admin_dashboard" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al Panel
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="action" value="admin_users">

            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Nombre o email..."
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Rol</label>
                <select name="role" class="form-select">
                    <option value="">Todos los roles</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="user" <?= $filters['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="is_active" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="card">
    <div class="card-header">
        <span class="badge bg-primary"><?= $users['total'] ?> usuarios encontrados</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users['data'])): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-people fs-1"></i>
                <p class="mt-2">No se encontraron usuarios</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Vehículos</th>
                            <th>Último acceso</th>
                            <th>Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users['data'] as $user): ?>
                            <tr>
                                <td><strong>#<?= $user['id'] ?></strong></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </a>
                                    <?php if (!empty($user['email_notifications'])): ?>
                                        <i class="bi bi-bell-fill text-success ms-1" title="Notificaciones activas"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Usuario</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $user['vehicle_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($user['last_login'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?action=admin_user_edit&id=<?= $user['id'] ?>"
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($user['id'] !== Auth::id()): ?>
                                            <form method="POST" action="index.php?action=admin_user_toggle" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>"
                                                        title="<?= $user['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                                    <i class="bi bi-<?= $user['is_active'] ? 'pause' : 'play' ?>"></i>
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal<?= $user['id'] ?>"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal de eliminación -->
                                    <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Confirmar Eliminación</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p>¿Estás seguro de eliminar al usuario <strong><?= htmlspecialchars($user['name']) ?></strong>?</p>
                                                    <p class="text-danger mb-0">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        Esta acción eliminará todos sus vehículos, registros y datos asociados.
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <form method="POST" action="index.php?action=admin_user_delete" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-trash me-1"></i>Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($users['last_page'] > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination mb-0 justify-content-center">
                            <?php for ($i = 1; $i <= $users['last_page']; $i++): ?>
                                <li class="page-item <?= $i === $users['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="index.php?action=admin_users&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&role=<?= urlencode($filters['role']) ?>&is_active=<?= urlencode($filters['is_active']) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
