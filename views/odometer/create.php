<?php $title = 'Registrar Kilometraje - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item"><a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></a></li>
        <li class="breadcrumb-item active">Registrar KM</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Registrar Kilometraje
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Info del vehículo -->
                <div class="alert alert-light border mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?= $vehicle['type'] === 'motorcycle' ? 'bicycle' : 'car-front' ?> fs-3 me-3"></i>
                        <div>
                            <strong><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></strong>
                            <br>
                            <small class="text-muted">KM actual: <?= number_format($vehicle['current_km']) ?> km</small>
                        </div>
                    </div>
                </div>

                <form method="POST" action="index.php?action=odometer_create&vehicle_id=<?= $vehicle['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-3">
                        <label for="km" class="form-label">Kilometraje Actual <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control" id="km" name="km"
                                   value="<?= htmlspecialchars($data['km'] ?? $vehicle['current_km']) ?>"
                                   min="<?= $vehicle['current_km'] ?>" required autofocus>
                            <span class="input-group-text">km</span>
                        </div>
                        <div class="form-text">Mínimo: <?= number_format($vehicle['current_km']) ?> km</div>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date"
                               value="<?= htmlspecialchars($data['date'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional)</label>
                        <input type="text" class="form-control" id="notes" name="notes"
                               value="<?= htmlspecialchars($data['notes'] ?? '') ?>"
                               placeholder="Ej: Revisión en taller, viaje...">
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
