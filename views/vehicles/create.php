<?php $title = 'Añadir Vehículo - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item active">Añadir Vehículo</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Añadir Nuevo Vehículo
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=vehicle_create" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <!-- Tipo de vehículo -->
                    <div class="mb-4">
                        <label class="form-label">Tipo de Vehículo</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeMoto" value="motorcycle"
                                   <?= ($data['type'] ?? 'motorcycle') === 'motorcycle' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="typeMoto">
                                <i class="bi bi-bicycle me-1"></i> Motocicleta
                            </label>

                            <input type="radio" class="btn-check" name="type" id="typeCar" value="car"
                                   <?= ($data['type'] ?? '') === 'car' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="typeCar">
                                <i class="bi bi-car-front me-1"></i> Coche
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Marca <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="brand" name="brand"
                                   value="<?= htmlspecialchars($data['brand'] ?? '') ?>" required
                                   placeholder="Ej: Yamaha, Honda, BMW...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Modelo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model"
                                   value="<?= htmlspecialchars($data['model'] ?? '') ?>" required
                                   placeholder="Ej: MT-07, CBR500R...">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="year" class="form-label">Año <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="year" name="year"
                                   value="<?= htmlspecialchars($data['year'] ?? date('Y')) ?>"
                                   min="1900" max="<?= date('Y') + 1 ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="displacement" class="form-label">Cilindrada (cc)</label>
                            <input type="number" class="form-control" id="displacement" name="displacement"
                                   value="<?= htmlspecialchars($data['displacement'] ?? '') ?>"
                                   min="0" max="10000" placeholder="Ej: 689">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="license_plate" class="form-label">Matrícula <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="license_plate" name="license_plate"
                                   value="<?= htmlspecialchars($data['license_plate'] ?? '') ?>" required
                                   placeholder="Ej: 1234 ABC">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_km" class="form-label">Kilometraje Actual</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="current_km" name="current_km"
                                       value="<?= htmlspecialchars($data['current_km'] ?? 0) ?>" min="0">
                                <span class="input-group-text">km</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="default_fuel_type_id" class="form-label">Combustible Predeterminado</label>
                            <select class="form-select" id="default_fuel_type_id" name="default_fuel_type_id">
                                <?php foreach ($fuelTypes ?? [] as $type): ?>
                                    <option value="<?= $type['id'] ?>"
                                            <?= ($data['default_fuel_type_id'] ?? 1) == $type['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Se usará por defecto al repostar</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="photo" class="form-label">Foto del Vehículo</label>
                            <input type="file" class="form-control" id="photo" name="photo"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">JPG, PNG, GIF, WebP. Máx: 5MB</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Guardar Vehículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
