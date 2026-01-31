<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-bar-chart me-2"></i>Estadísticas de Actividad</h1>
    <a href="index.php?action=admin_logs" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Logs
    </a>
</div>

<!-- Resumen superior -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #0d6efd;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted small mb-1">Total de Logs</div>
                        <div class="h4 mb-0"><?= number_format($stats['total']) ?></div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-clipboard-data fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #198754;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted small mb-1">Tipos de Acción</div>
                        <div class="h4 mb-0"><?= count($stats['top_actions']) ?></div>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-gear fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #ffc107;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted small mb-1">Usuarios Activos</div>
                        <div class="h4 mb-0"><?= count($stats['top_users']) ?></div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Acciones -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Top Acciones</h5>
            </div>
            <div class="card-body">
                <?php if (empty($stats['top_actions'])): ?>
                    <p class="text-muted text-center py-3">No hay datos disponibles</p>
                <?php else: ?>
                    <?php $maxCount = max(array_column($stats['top_actions'], 'count')); ?>
                    <?php foreach ($stats['top_actions'] as $action): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge bg-secondary"><?= htmlspecialchars($action['action']) ?></span>
                                <small class="text-muted"><?= number_format($action['count']) ?></small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary"
                                     style="width: <?= ($maxCount > 0 ? ($action['count'] / $maxCount * 100) : 0) ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Usuarios -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Usuarios Más Activos</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats['top_users'])): ?>
                    <p class="text-muted text-center py-4">No hay datos disponibles</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th class="text-end">Actividades</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['top_users'] as $i => $user): ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $i === 0 ? 'bg-warning text-dark' : ($i === 1 ? 'bg-secondary' : 'bg-dark') ?>">
                                                <?= $i + 1 ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small></td>
                                        <td class="text-end"><strong><?= number_format($user['count']) ?></strong></td>
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

<!-- Actividad Diaria -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Actividad Diaria (últimos 30 días)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($stats['daily'])): ?>
            <p class="text-muted text-center py-3">No hay datos de los últimos 30 días</p>
        <?php else: ?>
            <?php
            // Construir mapa de datos por fecha, rellenando días sin actividad
            $dailyMap = [];
            foreach ($stats['daily'] as $day) {
                $dailyMap[$day['date']] = (int) $day['count'];
            }
            $maxDaily = 0;
            $allDays = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = $dailyMap[$date] ?? 0;
                $allDays[] = ['date' => $date, 'count' => $count];
                if ($count > $maxDaily) $maxDaily = $count;
            }
            ?>
            <div class="d-flex align-items-end gap-1" style="height: 180px;">
                <?php foreach ($allDays as $day): ?>
                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-end" style="height: 100%; min-width: 0;">
                        <?php if ($day['count'] > 0): ?>
                            <small class="text-muted mb-1" style="font-size: 0.6rem;"><?= $day['count'] ?></small>
                            <div class="bg-primary rounded-top w-100"
                                 style="height: <?= ($maxDaily > 0 ? max(($day['count'] / $maxDaily * 130), 4) : 0) ?>px;">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-between mt-2 text-muted">
                <small><?= date('d/m', strtotime('-29 days')) ?></small>
                <small>Hoy: <?= date('d/m') ?></small>
            </div>
        <?php endif; ?>
    </div>
</div>
