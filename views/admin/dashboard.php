<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-speedometer2 me-2"></i>Panel de Administración</h1>
</div>

<!-- Estadísticas Principales -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Total Usuarios</h6>
                        <h2 class="card-title mb-0"><?= number_format($userStats['total']) ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
                <div class="mt-2 small">
                    <span class="text-white-50"><?= $userStats['active'] ?> activos</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?action=admin_users" class="text-white text-decoration-none small">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Total Vehículos</h6>
                        <h2 class="card-title mb-0"><?= number_format($totalVehicles) ?></h2>
                    </div>
                    <i class="bi bi-car-front-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Repostajes</h6>
                        <h2 class="card-title mb-0"><?= number_format($totalFuelLogs) ?></h2>
                    </div>
                    <i class="bi bi-fuel-pump-fill fs-1 opacity-50"></i>
                </div>
                <div class="mt-2 small">
                    <span class="text-white-50"><?= number_format($totalFuelCost, 2) ?> € total</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-50">Mantenimientos</h6>
                        <h2 class="card-title mb-0"><?= number_format($totalMaintenanceLogs) ?></h2>
                    </div>
                    <i class="bi bi-tools fs-1 opacity-50"></i>
                </div>
                <div class="mt-2 small">
                    <span class="opacity-50"><?= number_format($totalMaintCost, 2) ?> € total</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rápidas y Actividad -->
<div class="row g-4">
    <!-- Acciones Rápidas -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?action=admin_users" class="btn btn-outline-primary">
                        <i class="bi bi-people me-2"></i>Gestionar Usuarios
                    </a>
                    <a href="index.php?action=admin_settings" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-2"></i>Configuración General
                    </a>
                    <a href="index.php?action=admin_email" class="btn btn-outline-info">
                        <i class="bi bi-envelope me-2"></i>Configuración Email
                    </a>
                    <a href="index.php?action=admin_templates" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-text me-2"></i>Plantillas Email
                    </a>
                    <a href="index.php?action=admin_logs" class="btn btn-outline-warning">
                        <i class="bi bi-clock-history me-2"></i>Ver Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Actividad Reciente</h5>
                <a href="index.php?action=admin_logs" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivity)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No hay actividad reciente</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted"><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['description']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de Usuarios -->
<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribución de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="display-6 text-success"><?= $userStats['active'] ?></div>
                        <small class="text-muted">Activos</small>
                    </div>
                    <div class="col-6">
                        <div class="display-6 text-danger"><?= $userStats['inactive'] ?></div>
                        <small class="text-muted">Inactivos</small>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 25px;">
                    <?php
                    $activePercent = $userStats['total'] > 0 ? ($userStats['active'] / $userStats['total']) * 100 : 0;
                    ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $activePercent ?>%">
                        <?= round($activePercent) ?>% Activos
                    </div>
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= 100 - $activePercent ?>%">
                        <?= round(100 - $activePercent) ?>% Inactivos
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Resumen Económico</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="display-6 text-info"><?= number_format($totalFuelCost, 0) ?>€</div>
                        <small class="text-muted">Combustible</small>
                    </div>
                    <div class="col-6">
                        <div class="display-6 text-warning"><?= number_format($totalMaintCost, 0) ?>€</div>
                        <small class="text-muted">Mantenimiento</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <div class="h3"><?= number_format($totalFuelCost + $totalMaintCost, 2) ?> €</div>
                    <small class="text-muted">Gasto Total de la Plataforma</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de Mantenimiento Global -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-tools me-2 <?= !empty($maintenanceAlerts) ? 'text-warning' : 'text-success' ?>"></i>
                    Alertas de Mantenimiento
                </h5>
                <?php if (!empty($maintenanceAlerts)): ?>
                    <span class="badge bg-warning text-dark"><?= count($maintenanceAlerts) ?> pendiente<?= count($maintenanceAlerts) !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($maintenanceAlerts)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1 text-success"></i>
                        <p class="mt-2">Todos los mantenimientos están al día</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Vehículo</th>
                                    <th>Mantenimiento</th>
                                    <th>Fecha límite</th>
                                    <th>Km límite</th>
                                    <th>Km actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceAlerts as $alert): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($alert['user_name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($alert['brand'] . ' ' . $alert['model']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($alert['license_plate']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($alert['maintenance_type'] ?? 'Otro') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($alert['next_date']): ?>
                                            <?php
                                            $daysLeft = (strtotime($alert['next_date']) - strtotime('today')) / 86400;
                                            $dateClass = $daysLeft < 0 ? 'text-danger fw-bold' : ($daysLeft <= 3 ? 'text-warning fw-bold' : 'text-muted');
                                            ?>
                                            <span class="<?= $dateClass ?>">
                                                <?= date('d/m/Y', strtotime($alert['next_date'])) ?>
                                                <?php if ($daysLeft < 0): ?>
                                                    <small>(hace <?= abs((int)$daysLeft) ?> días)</small>
                                                <?php elseif ($daysLeft === 0): ?>
                                                    <small>(hoy)</small>
                                                <?php else: ?>
                                                    <small>(<?= (int)$daysLeft ?> días)</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($alert['next_km']): ?>
                                            <span class="<?= $alert['next_km'] <= $alert['current_km'] ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <?= number_format($alert['next_km']) ?> km
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= number_format($alert['current_km']) ?> km</strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
