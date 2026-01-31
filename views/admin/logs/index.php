<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-clock-history me-2"></i>Logs de Actividad</h1>
    <div class="d-flex gap-2">
        <a href="index.php?action=admin_logs_stats" class="btn btn-outline-info">
            <i class="bi bi-bar-chart me-1"></i>Estadísticas
        </a>
        <a href="index.php?action=admin_dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al Panel
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="action" value="admin_logs">

            <div class="col-md-2">
                <label class="form-label">Usuario</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"
                                <?= (string)($filters['user_id'] ?? '') === (string)$user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Acción</label>
                <select name="action_filter" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?= htmlspecialchars($action) ?>"
                                <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                            <?= htmlspecialchars($action) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>

            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="index.php?action=admin_logs" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de logs -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="badge bg-primary"><?= $logs['total'] ?> registros</span>

        <div class="d-flex gap-2">
            <?php
            $exportParams = http_build_query(array_filter([
                'action' => 'admin_logs_export',
                'user_id' => $filters['user_id'],
                'action_filter' => $filters['action'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to']
            ]));
            ?>
            <a href="index.php?<?= $exportParams ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                <i class="bi bi-trash me-1"></i>Limpiar Logs
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs['data'])): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No hay registros de actividad</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>Entidad</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs['data'] as $log): ?>
                            <tr>
                                <td>
                                    <small><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <span title="<?= htmlspecialchars($log['user_email'] ?? '') ?>">
                                            <?= htmlspecialchars($log['user_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sistema</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td>
                                    <?php if (!empty($log['entity_type'])): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['entity_type']) ?>
                                            <?php if (!empty($log['entity_id'])): ?>
                                                #<?= $log['entity_id'] ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($logs['last_page'] > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php
                            $queryParams = http_build_query(array_filter([
                                'action' => 'admin_logs',
                                'user_id' => $filters['user_id'],
                                'action_filter' => $filters['action'],
                                'date_from' => $filters['date_from'],
                                'date_to' => $filters['date_to']
                            ]));
                            ?>
                            <?php for ($i = 1; $i <= $logs['last_page']; $i++): ?>
                                <li class="page-item <?= $i === $logs['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?<?= $queryParams ?>&page=<?= $i ?>">
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

<!-- Modal Limpiar Logs -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Limpiar Logs</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_logs_clear">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-body">
                    <p>Selecciona qué registros quieres eliminar:</p>

                    <div class="mb-3">
                        <label class="form-label">Período a eliminar</label>
                        <select name="days" class="form-select">
                            <option value="0">Todos los registros</option>
                            <option value="7">Más de 7 días</option>
                            <option value="30">Más de 30 días</option>
                            <option value="60">Más de 60 días</option>
                            <option value="90" selected>Más de 90 días</option>
                            <option value="180">Más de 6 meses</option>
                            <option value="365">Más de 1 año</option>
                        </select>
                    </div>

                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <strong>Esta acción no se puede deshacer.</strong> Los registros eliminados se perderán permanentemente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar Logs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
