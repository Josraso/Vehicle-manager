<?php $title = 'Comparativa de Vehículos - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item active">Comparativa</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-bar-chart-bars me-2"></i>Comparativa de Vehículos
    </h1>
</div>

<!-- Tabla de comparación -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Métrica</th>
                    <?php foreach ($vehicles as $v): ?>
                        <th class="text-center">
                            <a href="index.php?action=vehicle_show&id=<?= $v['id'] ?>" class="text-white text-decoration-none">
                                <?= htmlspecialchars($v['brand']) ?> <?= htmlspecialchars($v['model']) ?>
                            </a>
                            <br><small class="fw-normal opacity-75"><?= htmlspecialchars($v['license_plate']) ?> · <?= $v['year'] ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Datos básicos -->
                <tr class="table-light">
                    <td colspan="<?= count($vehicles) + 1 ?>" class="fw-bold small text-uppercase text-muted py-2">Datos del vehículo</td>
                </tr>
                <tr>
                    <td>Tipo</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= $v['type'] === 'motorcycle' ? 'Motocicleta' : 'Turismo' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Cilindrada</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= $v['displacement'] ? number_format($v['displacement']) . ' cc' : '—' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Kilometraje actual</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center fw-bold"><?= number_format($v['current_km']) ?> km</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Recorrido registrado</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['km_driven']) ?> km</td>
                    <?php endforeach; ?>
                </tr>

                <!-- Eficiencia -->
                <tr class="table-light">
                    <td colspan="<?= count($vehicles) + 1 ?>" class="fw-bold small text-uppercase text-muted py-2">Eficiencia</td>
                </tr>
                <tr>
                    <td>Consumo (último tramo)</td>
                    <?php
                    // Encontrar el mejor consumo para destacarlo
                    $consumptions = array_filter(array_map(fn($v) => $v['consumption']['consumption'] ?? null, $vehicles));
                    $bestConsumption = !empty($consumptions) ? min($consumptions) : null;
                    ?>
                    <?php foreach ($vehicles as $v): ?>
                        <?php $cons = $v['consumption']['consumption'] ?? null; ?>
                        <td class="text-center <?= $cons !== null && $cons == $bestConsumption ? 'text-success fw-bold' : '' ?>">
                            <?= $cons !== null ? number_format($cons, 1) . ' L/100km' : '—' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Consumo medio (histórico)</td>
                    <?php
                    $avgConsumptions = array_filter(array_map(fn($v) => $v['stats']['avg_consumption'] > 0 ? $v['stats']['avg_consumption'] : null, $vehicles));
                    $bestAvgCons = !empty($avgConsumptions) ? min($avgConsumptions) : null;
                    ?>
                    <?php foreach ($vehicles as $v): ?>
                        <?php $avg = $v['stats']['avg_consumption'] > 0 ? $v['stats']['avg_consumption'] : null; ?>
                        <td class="text-center <?= $avg !== null && $avg == $bestAvgCons ? 'text-success fw-bold' : '' ?>">
                            <?= $avg !== null ? number_format($avg, 1) . ' L/100km' : '—' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Coste / km</td>
                    <?php
                    $cpks = array_filter(array_map(fn($v) => $v['cost_per_km'] > 0 ? $v['cost_per_km'] : null, $vehicles));
                    $bestCpk = !empty($cpks) ? min($cpks) : null;
                    ?>
                    <?php foreach ($vehicles as $v): ?>
                        <?php $cpk = $v['cost_per_km'] > 0 ? $v['cost_per_km'] : null; ?>
                        <td class="text-center <?= $cpk !== null && $cpk == $bestCpk ? 'text-success fw-bold' : '' ?>">
                            <?= $cpk !== null ? number_format($cpk, 3) . ' €/km' : '—' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Costes -->
                <tr class="table-light">
                    <td colspan="<?= count($vehicles) + 1 ?>" class="fw-bold small text-uppercase text-muted py-2">Costes este año</td>
                </tr>
                <tr>
                    <td>Combustible</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['yearly_fuel'], 2) ?> €</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Mantenimiento</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['yearly_maint'], 2) ?> €</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="fw-bold">Total este año</td>
                    <?php
                    $yearlyTotals = array_map(fn($v) => $v['yearly_fuel'] + $v['yearly_maint'], $vehicles);
                    $bestYearly = !empty($yearlyTotals) ? min($yearlyTotals) : null;
                    ?>
                    <?php foreach ($vehicles as $v): ?>
                        <?php $yt = $v['yearly_fuel'] + $v['yearly_maint']; ?>
                        <td class="text-center fw-bold <?= $bestYearly !== null && $yt == $bestYearly ? 'text-success' : '' ?>">
                            <?= number_format($yt, 2) ?> €
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Costes históricos -->
                <tr class="table-light">
                    <td colspan="<?= count($vehicles) + 1 ?>" class="fw-bold small text-uppercase text-muted py-2">Costes históricos</td>
                </tr>
                <tr>
                    <td>Total combustible</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['stats']['total_fuel_cost'], 2) ?> €</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Total mantenimiento</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['stats']['total_maint_cost'], 2) ?> €</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="fw-bold">Total acumulado</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center fw-bold"><?= number_format($v['stats']['total_cost'], 2) ?> €</td>
                    <?php endforeach; ?>
                </tr>

                <!-- Actividad -->
                <tr class="table-light">
                    <td colspan="<?= count($vehicles) + 1 ?>" class="fw-bold small text-uppercase text-muted py-2">Actividad</td>
                </tr>
                <tr>
                    <td>Repostajes</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= $v['stats']['total_refuels'] ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Mantenimientos</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= $v['stats']['total_maintenances'] ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Precio medio combustible</td>
                    <?php foreach ($vehicles as $v): ?>
                        <td class="text-center"><?= number_format($v['stats']['avg_price_liter'], 3) ?> €/L</td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Los valores en <strong class="text-success">verde</strong> indican el mejor resultado entre los vehículos comparados.
</p>
