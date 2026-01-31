<?php $title = 'Historial de Kilometraje - Vehicle Manager'; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?action=dashboard">Mis Vehículos</a></li>
        <li class="breadcrumb-item"><a href="index.php?action=vehicle_show&id=<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></a></li>
        <li class="breadcrumb-item active">Historial KM</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-speedometer2 me-2"></i>Historial de Kilometraje
    </h1>
    <a href="index.php?action=odometer_create&vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-info">
        <i class="bi bi-plus-lg me-1"></i> Registrar KM
    </a>
</div>

<!-- Gráfico de evolución -->
<?php if (!empty($evolution)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Evolución Mensual <?= date('Y') ?></h6>
    </div>
    <div class="card-body">
        <canvas id="kmChart" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de registros -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Registros de Kilometraje</h6>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="text-center py-4">
                <i class="bi bi-speedometer2 display-4 text-muted"></i>
                <p class="text-muted mt-2">No hay registros de kilometraje</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Kilometraje</th>
                            <th>Origen</th>
                            <th>Notas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                                <td class="text-end fw-bold"><?= number_format($log['km']) ?> km</td>
                                <td>
                                    <?php
                                    $sourceIcons = [
                                        'manual' => '<span class="badge bg-info">Manual</span>',
                                        'fuel' => '<span class="badge bg-success">Repostaje</span>',
                                        'maintenance' => '<span class="badge bg-warning text-dark">Mantenimiento</span>'
                                    ];
                                    echo $sourceIcons[$log['source']] ?? $log['source'];
                                    ?>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($log['notes'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php if ($log['source'] === 'manual'): ?>
                                        <form method="POST" action="index.php?action=odometer_delete" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este registro?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
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

<?php if (!empty($evolution)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('kmChart').getContext('2d');
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    const data = <?= json_encode(array_map(fn($e) => ['month' => $e['month'], 'km' => $e['km_driven']], $evolution)) ?>;
    const kmData = Array(12).fill(0);
    data.forEach(d => kmData[d.month - 1] = d.km);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'KM Recorridos',
                data: kmData,
                backgroundColor: 'rgba(13, 202, 240, 0.7)',
                borderColor: 'rgb(13, 202, 240)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => value.toLocaleString() + ' km'
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>
