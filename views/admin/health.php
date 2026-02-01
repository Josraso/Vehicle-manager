<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-activity me-2"></i>Salud del Sistema</h1>
</div>

<div class="row g-4">
    <!-- Cron -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Cron</h5>
            </div>
            <div class="card-body">
                <?php
                $cronOk = false;
                $cronAgo = '';
                if ($cronLastRun) {
                    $lastRunTs = strtotime($cronLastRun);
                    $minsSince = (time() - $lastRunTs) / 60;
                    $cronOk = $minsSince < 5;
                    if ($minsSince < 60) {
                        $cronAgo = round($minsSince) . ' minutos';
                    } elseif ($minsSince < 1440) {
                        $cronAgo = round($minsSince / 60) . ' horas';
                    } else {
                        $cronAgo = round($minsSince / 1440) . ' días';
                    }
                }
                ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-<?= $cronOk ? 'success' : ($cronLastRun ? 'warning' : 'danger') ?> <?= $cronOk ? 'text-white' : ($cronLastRun ? 'text-dark' : 'text-white') ?> d-flex align-items-center justify-content-center me-3"
                         style="width:44px;height:44px;flex-shrink:0;">
                        <i class="bi bi-<?= $cronOk ? 'check-lg' : 'exclamation-lg' ?> fs-5"></i>
                    </div>
                    <div>
                        <?php if ($cronLastRun): ?>
                            <strong>Última ejecución hace <?= $cronAgo ?></strong><br>
                            <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($cronLastRun)) ?></small>
                        <?php else: ?>
                            <strong class="text-danger">Nunca ejecutado</strong><br>
                            <small class="text-muted">El cron no ha corrido aún</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$cronOk): ?>
                <div class="alert alert-warning py-2 mb-0">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Añade esto a tu crontab:<br>
                        <code class="text-break">* * * * * curl -s "<?= htmlspecialchars($cronUrl) ?>" &gt; /dev/null 2>&amp;1</code>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cola de Emails -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Cola de Emails</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="display-6 text-warning"><?= $queueStats['pending'] ?></div>
                        <small class="text-muted">Pendientes</small>
                    </div>
                    <div class="col-4">
                        <div class="display-6 text-success"><?= $queueStats['sent'] ?></div>
                        <small class="text-muted">Enviados</small>
                    </div>
                    <div class="col-4">
                        <div class="display-6 text-danger"><?= $queueStats['failed'] ?></div>
                        <small class="text-muted">Fallidos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sistema -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cpu me-2"></i>Sistema</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">PHP:</td>
                        <td><strong><?= htmlspecialchars($phpVersion) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">MySQL:</td>
                        <td><strong><?= htmlspecialchars($mysqlVersion) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Espacio libre:</td>
                        <td>
                            <strong><?= number_format($diskFree / (1024*1024*1024), 2) ?> GB</strong>
                            <span class="text-muted">/ <?= number_format($diskTotal / (1024*1024*1024), 2) ?> GB</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Uso disco:</td>
                        <td>
                            <?php $diskUsedPercent = $diskTotal > 0 ? round((1 - $diskFree/$diskTotal) * 100) : 0; ?>
                            <div class="progress mb-1" style="height:8px;">
                                <div class="progress-bar bg-<?= $diskUsedPercent > 90 ? 'danger' : ($diskUsedPercent > 70 ? 'warning' : 'success') ?>"
                                     role="progressbar" style="width:<?= $diskUsedPercent ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $diskUsedPercent ?>% usado</small>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Tickets -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-headset me-2"></i>Tickets</h5>
            </div>
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <div class="display-5 <?= $openTickets > 0 ? 'text-warning' : 'text-success' ?>"><?= $openTickets ?></div>
                <small class="text-muted">Tickets abiertos</small>
                <?php if ($openTickets > 0): ?>
                <a href="index.php?action=admin_tickets&status=open" class="btn btn-sm btn-warning mt-3">
                    <i class="bi bi-headset me-1"></i>Ver tickets
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
