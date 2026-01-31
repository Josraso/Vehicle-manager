<?php $title = 'Editar Repostaje - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item"><a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></a></li>
        <li class="breadcrumb-item active">Editar Repostaje</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil me-2"></i>Editar Repostaje
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=fuel_edit&id=<?= $fuelLog['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= htmlspecialchars($fuelLog['date']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fuel_type_id" class="form-label">Tipo de Combustible</label>
                            <select class="form-select" id="fuel_type_id" name="fuel_type_id">
                                <?php foreach ($fuelTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>"
                                            <?= $fuelLog['fuel_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="km" class="form-label">Kilometraje <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="km" name="km"
                                   value="<?= htmlspecialchars($fuelLog['km']) ?>" min="0" required>
                            <span class="input-group-text">km</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="liters" class="form-label">Litros <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="liters" name="liters"
                                       value="<?= htmlspecialchars($fuelLog['liters']) ?>"
                                       step="0.01" min="0.01" required>
                                <span class="input-group-text">L</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="total_cost" class="form-label">Coste Total <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="total_cost" name="total_cost"
                                       value="<?= htmlspecialchars($fuelLog['total_cost']) ?>"
                                       step="0.01" min="0.01" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-3" id="pricePerLiter">
                        <i class="bi bi-calculator me-2"></i>
                        Precio por litro: <strong id="pplValue"><?= number_format($fuelLog['price_per_liter'], 3) ?></strong> €/L
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="full_tank" name="full_tank" value="1"
                                   <?= $fuelLog['full_tank'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="full_tank">
                                Depósito lleno
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <input type="text" class="form-control" id="notes" name="notes"
                               value="<?= htmlspecialchars($fuelLog['notes'] ?? '') ?>">
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('liters').addEventListener('input', calculatePPL);
document.getElementById('total_cost').addEventListener('input', calculatePPL);

function calculatePPL() {
    const liters = parseFloat(document.getElementById('liters').value) || 0;
    const cost = parseFloat(document.getElementById('total_cost').value) || 0;
    if (liters > 0 && cost > 0) {
        document.getElementById('pplValue').textContent = (cost / liters).toFixed(3);
    }
}
</script>
