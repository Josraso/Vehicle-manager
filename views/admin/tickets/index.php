<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-headset me-2"></i>Tickets de Soporte</h1>
    <div class="d-flex gap-2">
        <a href="index.php?action=admin_tickets&status="
           class="btn btn-sm btn-<?= $status === '' ? 'primary' : 'outline-primary' ?>">Todos</a>
        <a href="index.php?action=admin_tickets&status=open"
           class="btn btn-sm btn-<?= $status === 'open' ? 'warning text-dark' : 'outline-warning' ?>">
            Abiertos <?php if ($status !== 'open' && $openCount > 0): ?><span class="badge bg-danger ms-1"><?= $openCount ?></span><?php endif; ?>
        </a>
        <a href="index.php?action=admin_tickets&status=closed"
           class="btn btn-sm btn-<?= $status === 'closed' ? 'success' : 'outline-success' ?>">Cerrados</a>
    </div>
</div>

<?php if (empty($tickets['data'])): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">No hay tickets<?= $status !== '' ? ' ' . ($status === 'open' ? 'abiertos' : 'cerrados') : '' ?></p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="badge bg-primary"><?= $tickets['total'] ?> ticket<?= $tickets['total'] !== 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Respuesta</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets['data'] as $ticket): ?>
                    <tr class="<?= $ticket['status'] === 'open' && !$ticket['admin_reply'] ? 'table-warning' : '' ?>">
                        <td>
                            <a href="index.php?action=admin_ticket_detail&id=<?= $ticket['id'] ?>"
                               class="fw-bold text-decoration-none">#<?= $ticket['id'] ?></a>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($ticket['user_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($ticket['user_email']) ?></small>
                        </td>
                        <td>
                            <a href="index.php?action=admin_ticket_detail&id=<?= $ticket['id'] ?>"
                               class="text-decoration-none"><?= htmlspecialchars($ticket['subject']) ?></a>
                        </td>
                        <td>
                            <span class="badge bg-<?= $ticket['status'] === 'open' ? 'warning text-dark' : 'success' ?>">
                                <?= $ticket['status'] === 'open' ? 'Abierto' : 'Cerrado' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ticket['admin_reply']): ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Respondido</span>
                            <?php else: ?>
                                <span class="text-muted"><i class="bi bi-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Paginación -->
<?php if ($tickets['last_page'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $tickets['last_page']; $i++): ?>
        <li class="page-item <?= $i === $tickets['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="index.php?action=admin_tickets&status=<?= urlencode($status) ?>&page=<?= $i ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
