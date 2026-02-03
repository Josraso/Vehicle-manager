<?php $title = $vehicle['brand'] . ' ' . $vehicle['model'] . ' - Vehicle Manager'; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></li>
    </ol>
</nav>

<!-- Header del vehículo -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($vehicle['photo']): ?>
                        <img src="<?= htmlspecialchars($vehicle['photo']) ?>" alt="Foto" class="rounded" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-body-tertiary rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-<?= $vehicle['type'] === 'motorcycle' ? 'bicycle' : 'car-front' ?> display-6 text-muted"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="h3 mb-1">
                            <?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?>
                            <span class="badge bg-<?= $vehicle['type'] === 'motorcycle' ? 'primary' : 'secondary' ?> fs-6">
                                <?= $vehicle['type'] === 'motorcycle' ? 'Moto' : 'Coche' ?>
                            </span>
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar3 me-1"></i> <?= $vehicle['year'] ?>
                            <?php if ($vehicle['displacement']): ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-gear me-1"></i> <?= number_format($vehicle['displacement']) ?> cc
                            <?php endif; ?>
                            <span class="mx-2">|</span>
                            <i class="bi bi-card-text me-1"></i> <?= htmlspecialchars($vehicle['license_plate']) ?>
                            <span class="mx-2">|</span>
                            <i class="bi bi-speedometer2 me-1"></i> <?= number_format($vehicle['current_km']) ?> km
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="btn-group">
                    <a href="index.php?action=vehicle_edit&id=<?= $vehicle['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteVehicleModal">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recordatorios pendientes -->
<?php if (!empty($reminders)): ?>
<div class="alert alert-warning d-flex align-items-center mb-4">
    <i class="bi bi-bell-fill me-3 fs-4"></i>
    <div>
        <strong>Recordatorios pendientes:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($reminders as $reminder): ?>
                <li>
                    <strong><?= htmlspecialchars($reminder['type_name']) ?></strong>
                    <?php if ($reminder['next_km']): ?>
                        - a los <?= number_format($reminder['next_km']) ?> km
                    <?php endif; ?>
                    <?php if ($reminder['next_date']): ?>
                        - antes del <?= date('d/m/Y', strtotime($reminder['next_date'])) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- Estadísticas rápidas: Costes -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-fuel-pump text-success fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Combustible</h6>
                <p class="h5 mb-0"><?= number_format($yearlyFuel, 2) ?> €</p>
                <small class="text-muted">este año</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-wrench text-warning fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Mantenimiento</h6>
                <p class="h5 mb-0"><?= number_format($yearlyMaint, 2) ?> €</p>
                <small class="text-muted">este año</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-cash-stack text-danger fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Total Gastado</h6>
                <p class="h5 mb-0"><?= number_format($stats['total_cost'], 2) ?> €</p>
                <small class="text-muted">siempre</small>
            </div>
        </div>
    </div>
</div>
<!-- Estadísticas rápidas: Eficiencia -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-droplet text-primary fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Consumo</h6>
                <p class="h5 mb-0">
                    <?= $consumption ? number_format($consumption['consumption'], 1) : '--' ?> L/100km
                </p>
                <small class="text-muted">último tramo</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-coin text-success fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Coste / km</h6>
                <p class="h5 mb-0">
                    <?= $costPerKm > 0 ? number_format($costPerKm, 3) : '--' ?> €/km
                </p>
                <small class="text-muted">total acumulado</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="bi bi-speedometer2 text-info fs-3"></i>
                <h6 class="card-title mt-2 mb-1">Recorrido</h6>
                <p class="h5 mb-0"><?= number_format($kmDriven) ?> km</p>
                <small class="text-muted">desde inicio</small>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="index.php?action=fuel_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-success">
        <i class="bi bi-fuel-pump me-1"></i> Añadir Repostaje
    </a>
    <a href="index.php?action=maintenance_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-warning">
        <i class="bi bi-wrench me-1"></i> Añadir Mantenimiento
    </a>
    <a href="index.php?action=odometer_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-info">
        <i class="bi bi-speedometer me-1"></i> Registrar KM
    </a>
    <a href="index.php?action=stats&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-primary">
        <i class="bi bi-graph-up me-1"></i> Estadísticas
    </a>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download me-1"></i> Exportar
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="index.php?action=export_fuel_csv&vehicle_id=<?= $vehicle['id'] ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Repostajes CSV</a></li>
            <li><a class="dropdown-item" href="index.php?action=export_maintenance_csv&vehicle_id=<?= $vehicle['id'] ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Mantenimientos CSV</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="index.php?action=export_backup_csv&vehicle_id=<?= $vehicle['id'] ?>"><i class="bi bi-cloud-download me-2"></i>Backup Completo</a></li>
            <li><a class="dropdown-item" href="index.php?action=export_pdf&vehicle_id=<?= $vehicle['id'] ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2"></i>Informe PDF</a></li>
        </ul>
    </div>
</div>

<!-- Tabs: Repostajes / Mantenimientos / Kilometraje -->
<ul class="nav nav-tabs" id="vehicleTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="fuel-tab" data-bs-toggle="tab" data-bs-target="#fuel-pane" type="button">
            <i class="bi bi-fuel-pump me-1"></i> Repostajes
            <span class="badge bg-success ms-1"><?= count($fuelLogs) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance-pane" type="button">
            <i class="bi bi-wrench me-1"></i> Mantenimientos
            <span class="badge bg-warning text-dark ms-1"><?= count($maintenanceLogs) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="odometer-tab" data-bs-toggle="tab" data-bs-target="#odometer-pane" type="button">
            <i class="bi bi-speedometer me-1"></i> Historial KM
            <span class="badge bg-info ms-1"><?= count($odometerLogs ?? []) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="vehicleTabsContent">
    <!-- Tab Repostajes -->
    <div class="tab-pane fade show active" id="fuel-pane" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                <?php if (empty($fuelLogs)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-fuel-pump display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay repostajes registrados</p>
                        <a href="index.php?action=fuel_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Añadir Primer Repostaje
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Filtro por fechas -->
                    <div class="d-flex gap-2 align-items-end mb-3 flex-wrap" id="fuelFilterBar">
                        <div>
                            <label class="form-label small mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="fuelDateFrom" onchange="filterTable('fuel')">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="fuelDateTo" onchange="filterTable('fuel')">
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="resetFilter('fuel')">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </button>
                        <span class="text-muted small ms-auto" id="fuelCount"><?= count($fuelLogs) ?> registros</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="fuelTable">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th class="text-end">KM</th>
                                    <th class="text-end">Litros</th>
                                    <th class="text-end">€/L</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">L/100km</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelLogs as $log): ?>
                                    <tr data-date="<?= htmlspecialchars($log['date']) ?>">
                                        <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                                        <td>
                                            <span class="badge bg-body-secondary text-body">
                                                <?= htmlspecialchars($log['fuel_type_name'] ?? 'N/A') ?>
                                            </span>
                                            <?php if (!$log['full_tank']): ?>
                                                <span class="badge bg-warning text-dark" title="Depósito parcial">P</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= number_format($log['km']) ?></td>
                                        <td class="text-end"><?= number_format($log['liters'], 2) ?></td>
                                        <td class="text-end"><?= number_format($log['price_per_liter'], 3) ?> €</td>
                                        <td class="text-end fw-bold"><?= number_format($log['total_cost'], 2) ?> €</td>
                                        <td class="text-end small text-muted"><?= $log['row_consumption'] !== null ? number_format($log['row_consumption'], 1) : '—' ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?action=fuel_edit&id=<?= $log['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                        onclick="confirmDelete('fuel', <?= $log['id'] ?>, '<?= date('d/m/Y', strtotime($log['date'])) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Tab Mantenimientos -->
    <div class="tab-pane fade" id="maintenance-pane" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                <?php if (empty($maintenanceLogs)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-wrench display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay mantenimientos registrados</p>
                        <a href="index.php?action=maintenance_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-warning btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Añadir Primer Mantenimiento
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Filtro por fechas -->
                    <div class="d-flex gap-2 align-items-end mb-3 flex-wrap" id="maintFilterBar">
                        <div>
                            <label class="form-label small mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="maintDateFrom" onchange="filterTable('maint')">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="maintDateTo" onchange="filterTable('maint')">
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="resetFilter('maint')">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </button>
                        <span class="text-muted small ms-auto" id="maintCount"><?= count($maintenanceLogs) ?> registros</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="maintTable">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th class="text-end">KM</th>
                                    <th class="text-end">Coste</th>
                                    <th>Próximo</th>
                                    <th>Notas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceLogs as $log): ?>
                                    <tr data-date="<?= htmlspecialchars($log['date']) ?>">
                                        <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                                        <td><?= htmlspecialchars($log['type_name'] ?? 'N/A') ?></td>
                                        <td class="text-end"><?= number_format($log['km']) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($log['cost'], 2) ?> €</td>
                                        <td class="small">
                                            <?php if ($log['next_km']): ?>
                                                <span class="text-muted"><?= number_format($log['next_km']) ?> km</span>
                                            <?php endif; ?>
                                            <?php if ($log['next_date']): ?>
                                                <span class="text-muted"><?= date('d/m/Y', strtotime($log['next_date'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted" style="max-width: 150px;">
                                            <?= htmlspecialchars(substr($log['notes'] ?? '', 0, 50)) ?>
                                            <?= strlen($log['notes'] ?? '') > 50 ? '...' : '' ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?action=maintenance_edit&id=<?= $log['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                        onclick="confirmDelete('maintenance', <?= $log['id'] ?>, '<?= htmlspecialchars($log['type_name'] ?? '') ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Tab Historial Kilometraje -->
    <div class="tab-pane fade" id="odometer-pane" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                <?php if (empty($odometerLogs)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-speedometer display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay registros de kilometraje</p>
                        <a href="index.php?action=odometer_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Registrar Primer KM
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Kilometraje</th>
                                    <th>Fuente</th>
                                    <th>Notas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($odometerLogs as $log): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($log['km']) ?> km</td>
                                        <td>
                                            <?php
                                            $sourceLabels = [
                                                'manual' => '<span class="badge bg-secondary">Manual</span>',
                                                'fuel' => '<span class="badge bg-success">Repostaje</span>',
                                                'maintenance' => '<span class="badge bg-warning text-dark">Mantenimiento</span>'
                                            ];
                                            echo $sourceLabels[$log['source']] ?? '<span class="badge bg-secondary">Otro</span>';
                                            ?>
                                        </td>
                                        <td class="small text-muted" style="max-width: 200px;">
                                            <?= htmlspecialchars(substr($log['notes'] ?? '', 0, 50)) ?>
                                            <?= strlen($log['notes'] ?? '') > 50 ? '...' : '' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($log['source'] === 'manual'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" title="Eliminar"
                                                        onclick="confirmDelete('odometer', <?= $log['id'] ?>, '<?= date('d/m/Y', strtotime($log['date'])) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Auto</span>
                                            <?php endif; ?>
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

<!-- Modal Eliminar Vehículo -->
<div class="modal fade" id="deleteVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar <strong><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Esta acción eliminará también todos los repostajes y mantenimientos asociados.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="index.php?action=vehicle_delete" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="id" value="<?= $vehicle['id'] ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Item -->
<div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar este registro: <strong id="deleteItemName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" id="deleteItemForm" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="id" id="deleteItemId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(type, id, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemForm').action = 'index.php?action=' + type + '_delete';
    new bootstrap.Modal(document.getElementById('deleteItemModal')).show();
}

// Filtro por rango de fechas
const filterConfig = {
    fuel: { tableId: 'fuelTable', fromId: 'fuelDateFrom', toId: 'fuelDateTo', countId: 'fuelCount' },
    maint: { tableId: 'maintTable', fromId: 'maintDateFrom', toId: 'maintDateTo', countId: 'maintCount' }
};

function filterTable(prefix) {
    const cfg = filterConfig[prefix];
    const table = document.getElementById(cfg.tableId);
    if (!table) return;

    const from = document.getElementById(cfg.fromId).value;
    const to   = document.getElementById(cfg.toId).value;
    const rows = table.querySelectorAll('tbody tr');
    let visible = 0;

    rows.forEach(row => {
        const date = row.dataset.date; // YYYY-MM-DD
        let show = true;
        if (from && date < from) show = false;
        if (to && date > to)     show = false;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById(cfg.countId).textContent = visible + ' registros';
}

function resetFilter(prefix) {
    const cfg = filterConfig[prefix];
    document.getElementById(cfg.fromId).value = '';
    document.getElementById(cfg.toId).value = '';
    filterTable(prefix);
}
</script>
