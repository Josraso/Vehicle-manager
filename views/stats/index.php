<?php $title = 'Estadísticas - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item"><a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></a></li>
        <li class="breadcrumb-item active">Estadísticas</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-graph-up me-2"></i>Estadísticas
    </h1>
    <div class="d-flex gap-2">
        <!-- Selector de año -->
        <select class="form-select form-select-sm" id="yearSelector" style="width: auto;">
            <?php foreach ($availableYears as $year): ?>
                <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Resumen general -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-success">
            <div class="card-body">
                <i class="bi bi-fuel-pump text-success fs-2"></i>
                <h6 class="mt-2 mb-1">Total Combustible</h6>
                <p class="h4 mb-0"><?= number_format($stats['total_fuel_cost'], 2) ?> €</p>
                <small class="text-muted"><?= $stats['total_refuels'] ?> repostajes</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-warning">
            <div class="card-body">
                <i class="bi bi-wrench text-warning fs-2"></i>
                <h6 class="mt-2 mb-1">Total Mantenimiento</h6>
                <p class="h4 mb-0"><?= number_format($stats['total_maint_cost'], 2) ?> €</p>
                <small class="text-muted"><?= $stats['total_maintenances'] ?> operaciones</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-primary">
            <div class="card-body">
                <i class="bi bi-droplet text-primary fs-2"></i>
                <h6 class="mt-2 mb-1">Consumo Medio</h6>
                <p class="h4 mb-0"><?= $stats['avg_consumption'] > 0 ? number_format($stats['avg_consumption'], 1) : '--' ?> L/100km</p>
                <small class="text-muted"><?= number_format($stats['total_liters'], 0) ?> litros totales</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-danger">
            <div class="card-body">
                <i class="bi bi-cash-stack text-danger fs-2"></i>
                <h6 class="mt-2 mb-1">Gasto Total</h6>
                <p class="h4 mb-0"><?= number_format($stats['total_cost'], 2) ?> €</p>
                <small class="text-muted">
                    <?php
                    $costPerKm = $vehicle['current_km'] > 0 ? $stats['total_cost'] / $vehicle['current_km'] : 0;
                    echo number_format($costPerKm, 3) . ' €/km';
                    ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de combustible -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-fuel-pump me-2"></i>Estadísticas Combustible</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Precio medio por litro:</td>
                        <td class="text-end fw-bold"><?= number_format($fuelStats['avg_price'], 3) ?> €/L</td>
                    </tr>
                    <tr>
                        <td>Precio mínimo:</td>
                        <td class="text-end text-success"><?= number_format($fuelStats['min_price'], 3) ?> €/L</td>
                    </tr>
                    <tr>
                        <td>Precio máximo:</td>
                        <td class="text-end text-danger"><?= number_format($fuelStats['max_price'], 3) ?> €/L</td>
                    </tr>
                    <tr>
                        <td>Total litros:</td>
                        <td class="text-end"><?= number_format($fuelStats['total_liters'], 2) ?> L</td>
                    </tr>
                    <tr>
                        <td>Total gastado:</td>
                        <td class="text-end fw-bold"><?= number_format($fuelStats['total_cost'], 2) ?> €</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-wrench me-2"></i>Estadísticas Mantenimiento</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Total operaciones:</td>
                        <td class="text-end fw-bold"><?= $maintStats['count'] ?></td>
                    </tr>
                    <tr>
                        <td>Coste medio:</td>
                        <td class="text-end"><?= number_format($maintStats['avg_cost'], 2) ?> €</td>
                    </tr>
                    <tr>
                        <td>Total gastado:</td>
                        <td class="text-end fw-bold"><?= number_format($maintStats['total_cost'], 2) ?> €</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Gastos Mensuales <?= $selectedYear ?></h6>
            </div>
            <div class="card-body">
                <canvas id="expensesChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribución de Gastos</h6>
            </div>
            <div class="card-body">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-speedometer me-2"></i>Kilómetros Recorridos <?= $selectedYear ?></h6>
    </div>
    <div class="card-body">
        <canvas id="kmChart" height="80"></canvas>
    </div>
</div>

<script>
const chartData = <?= json_encode($chartData) ?>;
const months = <?= json_encode($chartData['labels']) ?>;

// Gráfico de gastos mensuales
new Chart(document.getElementById('expensesChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Combustible',
                data: chartData.fuel,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgb(40, 167, 69)',
                borderWidth: 1
            },
            {
                label: 'Mantenimiento',
                data: chartData.maintenance,
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgb(255, 193, 7)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: { callback: value => value + ' €' }
            }
        }
    }
});

// Gráfico de distribución
const totalFuel = chartData.fuel.reduce((a, b) => a + b, 0);
const totalMaint = chartData.maintenance.reduce((a, b) => a + b, 0);

new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Combustible', 'Mantenimiento'],
        datasets: [{
            data: [totalFuel, totalMaint],
            backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(255, 193, 7, 0.7)'],
            borderColor: ['rgb(40, 167, 69)', 'rgb(255, 193, 7)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Gráfico de km
new Chart(document.getElementById('kmChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'KM Recorridos',
            data: chartData.km,
            fill: true,
            backgroundColor: 'rgba(13, 202, 240, 0.2)',
            borderColor: 'rgb(13, 202, 240)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: value => value.toLocaleString() + ' km' }
            }
        }
    }
});

// Cambiar año
document.getElementById('yearSelector').addEventListener('change', function() {
    window.location.href = 'index.php?action=stats&vehicle_id=<?= $vehicle['id'] ?>&year=' + this.value;
});
</script>
