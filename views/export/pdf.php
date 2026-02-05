<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe - <?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { color: #666; }
        .vehicle-info {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .vehicle-info h2 { font-size: 18px; margin-bottom: 10px; }
        .vehicle-info table { width: 100%; }
        .vehicle-info td { padding: 3px 10px 3px 0; }
        .section { margin-bottom: 25px; }
        .section h3 {
            font-size: 14px;
            background: #333;
            color: white;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.data th, table.data td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        table.data th {
            background: #f0f0f0;
            font-weight: bold;
        }
        table.data tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary h4 { margin-bottom: 10px; }
        .summary-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .summary-item { flex: 1; min-width: 150px; }
        .summary-item .label { color: #666; font-size: 11px; }
        .summary-item .value { font-size: 16px; font-weight: bold; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">
            Cerrar
        </button>
    </div>

    <div class="header">
        <h1>INFORME DE VEHÍCULO</h1>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="vehicle-info">
        <h2><?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?></h2>
        <table>
            <tr>
                <td><strong>Tipo:</strong> <?= $vehicle['type'] === 'motorcycle' ? 'Motocicleta' : 'Coche' ?></td>
                <td><strong>Año:</strong> <?= $vehicle['year'] ?></td>
                <td><strong>Matrícula:</strong> <?= htmlspecialchars($vehicle['license_plate']) ?></td>
            </tr>
            <tr>
                <td><strong>Cilindrada:</strong> <?= $vehicle['displacement'] ? number_format($vehicle['displacement']) . ' cc' : 'N/A' ?></td>
                <td><strong>KM Actual:</strong> <?= number_format($vehicle['current_km']) ?> km</td>
                <td></td>
            </tr>
        </table>
    </div>

    <?php if ($stats): ?>
    <div class="summary">
        <h4>RESUMEN DE GASTOS</h4>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Combustible</div>
                <div class="value"><?= number_format($stats['total_fuel_cost'], 2) ?> €</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Mantenimiento</div>
                <div class="value"><?= number_format($stats['total_maint_cost'], 2) ?> €</div>
            </div>
            <div class="summary-item">
                <div class="label">Total General</div>
                <div class="value"><?= number_format($stats['total_cost'], 2) ?> €</div>
            </div>
            <div class="summary-item">
                <div class="label">Consumo Medio</div>
                <div class="value"><?= $stats['avg_consumption'] > 0 ? number_format($stats['avg_consumption'], 1) . ' L/100km' : 'N/A' ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Coste / km</div>
                <div class="value"><?= $costPerKm > 0 ? number_format($costPerKm, 3) . ' €/km' : 'N/A' ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Recorrido</div>
                <div class="value"><?= number_format($kmDriven) ?> km</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($fuelLogs)): ?>
    <div class="section">
        <h3>HISTORIAL DE REPOSTAJES (<?= count($fuelLogs) ?>)</h3>
        <table class="data">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th class="text-right">KM</th>
                    <th class="text-right">Litros</th>
                    <th class="text-right">€/L</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">L/100km</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalLiters = 0;
                $totalFuelCost = 0;
                foreach ($fuelLogs as $log):
                    $totalLiters += $log['liters'];
                    $totalFuelCost += $log['total_cost'];
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                    <td><?= htmlspecialchars($log['fuel_type_name'] ?? 'N/A') ?></td>
                    <td class="text-right"><?= number_format($log['km']) ?></td>
                    <td class="text-right"><?= number_format($log['liters'], 2) ?></td>
                    <td class="text-right"><?= number_format($log['price_per_liter'], 3) ?> €</td>
                    <td class="text-right"><?= number_format($log['total_cost'], 2) ?> €</td>
                    <td class="text-right"><?= $log['row_consumption'] !== null ? number_format($log['row_consumption'], 1) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #f0f0f0;">
                    <td colspan="3">TOTALES</td>
                    <td class="text-right"><?= number_format($totalLiters, 2) ?> L</td>
                    <td></td>
                    <td class="text-right"><?= number_format($totalFuelCost, 2) ?> €</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($maintLogs)): ?>
    <div class="section">
        <h3>HISTORIAL DE MANTENIMIENTOS (<?= count($maintLogs) ?>)</h3>
        <table class="data">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th class="text-right">KM</th>
                    <th class="text-right">Coste</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalMaintCost = 0;
                foreach ($maintLogs as $log):
                    $totalMaintCost += $log['cost'];
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                    <td><?= htmlspecialchars($log['type_name'] ?? 'N/A') ?></td>
                    <td class="text-right"><?= number_format($log['km']) ?></td>
                    <td class="text-right"><?= number_format($log['cost'], 2) ?> €</td>
                    <td><?= htmlspecialchars(substr($log['notes'] ?? '', 0, 100)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #f0f0f0;">
                    <td colspan="3">TOTAL</td>
                    <td class="text-right"><?= number_format($totalMaintCost, 2) ?> €</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Vehicle Manager - Control de Vehículos</p>
        <p>Informe generado automáticamente</p>
    </div>
</body>
</html>
