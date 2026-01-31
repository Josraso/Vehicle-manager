<?php $title = 'Añadir Mantenimiento - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item"><a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></a></li>
        <li class="breadcrumb-item active">Añadir Mantenimiento</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0">
                    <i class="bi bi-wrench me-2"></i>Nuevo Mantenimiento
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

                <form method="POST" action="index.php?action=maintenance_create&vehicle_id=<?= $vehicle['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-3">
                        <label for="maintenance_type_id" class="form-label">Tipo de Mantenimiento <span class="text-danger">*</span></label>
                        <select class="form-select" id="maintenance_type_id" name="maintenance_type_id" required>
                            <?php foreach ($maintenanceTypes as $type): ?>
                                <option value="<?= $type['id'] ?>"
                                        <?= ($data['maintenance_type_id'] ?? 1) == $type['id'] ? 'selected' : '' ?>
                                        data-km="<?= $type['default_km_interval'] ?? '' ?>"
                                        data-days="<?= $type['default_days_interval'] ?? '' ?>">
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= htmlspecialchars($data['date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="km" class="form-label">Kilometraje <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="km" name="km"
                                       value="<?= htmlspecialchars($data['km'] ?? $vehicle['current_km']) ?>"
                                       min="0" required>
                                <span class="input-group-text">km</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cost" class="form-label">Coste</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="cost" name="cost"
                                   value="<?= htmlspecialchars($data['cost'] ?? '0') ?>"
                                   step="0.01" min="0">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>

                    <!-- Próximo mantenimiento (editable) -->
                    <div class="card bg-light border mb-3">
                        <div class="card-header py-2">
                            <i class="bi bi-bell me-2"></i>
                            <strong>Próximo mantenimiento</strong>
                            <small class="text-muted ms-2">(opcional, editable)</small>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="next_km" class="form-label small mb-1">Próximo a los KM</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="next_km" name="next_km"
                                               value="<?= htmlspecialchars($data['next_km'] ?? '') ?>"
                                               min="0" placeholder="Ej: 50000">
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="next_date" class="form-label small mb-1">Próxima fecha</label>
                                    <input type="date" class="form-control form-control-sm" id="next_date" name="next_date"
                                           value="<?= htmlspecialchars($data['next_date'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-text small">
                                <i class="bi bi-info-circle me-1"></i>
                                Puedes establecer recordatorios personalizados o dejar que se calculen automáticamente según el tipo de mantenimiento.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Ej: Cambio de aceite Motul 5100, filtro Mann..."><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-lg me-1"></i> Guardar Mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const typeSelect = document.getElementById('maintenance_type_id');
const kmInput = document.getElementById('km');
const dateInput = document.getElementById('date');

typeSelect.addEventListener('change', updateReminderInfo);
kmInput.addEventListener('input', updateReminderInfo);
dateInput.addEventListener('change', updateReminderInfo);

function updateReminderInfo() {
    const selected = typeSelect.options[typeSelect.selectedIndex];
    const kmInterval = parseInt(selected.dataset.km) || 0;
    const daysInterval = parseInt(selected.dataset.days) || 0;
    const currentKm = parseInt(kmInput.value) || 0;
    const currentDate = dateInput.value;

    let hasReminder = false;
    let infoHtml = '';

    if (kmInterval > 0) {
        const nextKm = currentKm + kmInterval;
        infoHtml += `A los ${nextKm.toLocaleString()} km`;
        hasReminder = true;
    }

    if (daysInterval > 0 && currentDate) {
        const nextDate = new Date(currentDate);
        nextDate.setDate(nextDate.getDate() + daysInterval);
        const formattedDate = nextDate.toLocaleDateString('es-ES');
        if (hasReminder) infoHtml += ' o ';
        infoHtml += `el ${formattedDate}`;
        hasReminder = true;
    }

    if (hasReminder) {
        document.getElementById('reminderInfo').style.display = 'block';
        document.getElementById('reminderInfo').querySelector('small').innerHTML =
            '<i class="bi bi-bell me-2"></i>Próximo recordatorio: ' + infoHtml;
    } else {
        document.getElementById('reminderInfo').style.display = 'none';
    }
}

updateReminderInfo();
</script>
