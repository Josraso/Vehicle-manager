<?php $title = 'Mis Vehículos - Vehicle Manager'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-grid-fill me-2"></i>Mis Vehículos
    </h1>
    <div class="d-flex gap-2">
        <?php if (count($vehicles) >= 2): ?>
            <a href="index.php?action=compare" class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart-bars me-1"></i> Comparar
            </a>
        <?php endif; ?>
        <a href="index.php?action=vehicle_create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Añadir Vehículo
        </a>
    </div>
</div>

<!-- Resumen global -->
<?php if (!empty($vehicles)): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-success">
            <div class="card-body py-3">
                <i class="bi bi-fuel-pump text-success fs-4"></i>
                <h6 class="mt-1 mb-0">Combustible</h6>
                <p class="h5 mb-0"><?= number_format($globalStats['yearly_fuel'], 2) ?> €</p>
                <small class="text-muted">este año</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-warning">
            <div class="card-body py-3">
                <i class="bi bi-wrench text-warning fs-4"></i>
                <h6 class="mt-1 mb-0">Mantenimiento</h6>
                <p class="h5 mb-0"><?= number_format($globalStats['yearly_maint'], 2) ?> €</p>
                <small class="text-muted">este año</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-danger">
            <div class="card-body py-3">
                <i class="bi bi-cash-stack text-danger fs-4"></i>
                <h6 class="mt-1 mb-0">Gasto Total</h6>
                <p class="h5 mb-0"><?= number_format($globalStats['yearly_total'], 2) ?> €</p>
                <small class="text-muted">este año</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-<?= $totalReminders > 0 ? 'warning' : 'secondary' ?>">
            <div class="card-body py-3">
                <i class="bi bi-bell<?= $totalReminders > 0 ? '-fill text-warning' : ' text-muted' ?> fs-4"></i>
                <h6 class="mt-1 mb-0">Recordatorios</h6>
                <p class="h5 mb-0"><?= $totalReminders ?></p>
                <small class="text-muted">pendiente<?= $totalReminders !== 1 ? 's' : '' ?></small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($vehicles)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-motorcycle display-1 text-muted mb-3"></i>
            <h5 class="text-muted">No tienes vehículos registrados</h5>
            <p class="text-muted mb-4">Añade tu primer vehículo para empezar a registrar repostajes y mantenimientos.</p>
            <a href="index.php?action=vehicle_create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Añadir Mi Primer Vehículo
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($vehicles as $vehicle): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 vehicle-card">
                    <?php if ($vehicle['photo']): ?>
                        <img src="<?= htmlspecialchars($vehicle['photo']) ?>" class="card-img-top vehicle-photo" alt="Foto del vehículo">
                    <?php else: ?>
                        <div class="card-img-top vehicle-photo-placeholder bg-body-secondary d-flex align-items-center justify-content-center">
                            <i class="bi bi-<?= $vehicle['type'] === 'motorcycle' ? 'bicycle' : 'car-front' ?> display-1 text-muted"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">
                                <?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?>
                            </h5>
                            <span class="badge bg-<?= $vehicle['type'] === 'motorcycle' ? 'primary' : 'secondary' ?>">
                                <?= $vehicle['type'] === 'motorcycle' ? 'Moto' : 'Coche' ?>
                            </span>
                        </div>

                        <p class="card-text text-muted small mb-3">
                            <i class="bi bi-calendar3 me-1"></i> <?= $vehicle['year'] ?>
                            <?php if ($vehicle['displacement']): ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-gear me-1"></i> <?= number_format($vehicle['displacement']) ?> cc
                            <?php endif; ?>
                            <br>
                            <i class="bi bi-card-text me-1"></i> <?= htmlspecialchars($vehicle['license_plate']) ?>
                        </p>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-body-tertiary rounded p-2 text-center">
                                    <div class="small text-muted">Kilometraje</div>
                                    <div class="fw-bold"><?= number_format($vehicle['current_km']) ?> km</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-body-tertiary rounded p-2 text-center">
                                    <div class="small text-muted">Gasto Total</div>
                                    <div class="fw-bold"><?= number_format($vehicle['stats']['total_cost'], 2) ?> €</div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($vehicle['reminders'])): ?>
                            <div class="alert alert-warning py-2 px-3 mb-3 small">
                                <i class="bi bi-bell-fill me-1"></i>
                                <?= count($vehicle['reminders']) ?> recordatorio(s) pendiente(s)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                <i class="bi bi-eye me-1"></i> Ver Detalle
                            </a>
                            <a href="index.php?action=fuel_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-success btn-sm" title="Añadir Repostaje">
                                <i class="bi bi-fuel-pump"></i>
                            </a>
                            <a href="index.php?action=maintenance_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-warning btn-sm" title="Añadir Mantenimiento">
                                <i class="bi bi-wrench"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
