<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-headset me-2"></i>Mis Tickets</h1>
    <a href="index.php?action=ticket_create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Ticket
    </a>
</div>

<?php if (empty($tickets)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">No tienes tickets aún</p>
        <a href="index.php?action=ticket_create" class="btn btn-primary mt-2">
            <i class="bi bi-plus-lg me-1"></i>Crear Ticket
        </a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Respuesta</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><strong>#<?= $ticket['id'] ?></strong></td>
                        <td><?= htmlspecialchars($ticket['subject']) ?></td>
                        <td>
                            <span class="badge bg-<?= $ticket['status'] === 'open' ? 'warning text-dark' : 'success' ?>">
                                <?= $ticket['status'] === 'open' ? 'Abierto' : 'Cerrado' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ticket['admin_reply']): ?>
                                <span class="text-success"><i class="bi bi-check-circle me-1"></i>Respondido</span>
                            <?php else: ?>
                                <span class="text-muted"><i class="bi bi-clock me-1"></i>Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></small></td>
                    </tr>
                    <?php if ($ticket['admin_reply']): ?>
                    <tr class="table-success">
                        <td colspan="5" class="ps-4">
                            <small class="text-success">
                                <i class="bi bi-reply me-1"></i><strong>Respuesta del administrador</strong>
                                (<?= date('d/m/Y H:i', strtotime($ticket['replied_at'])) ?>):
                            </small><br>
                            <span class="text-success" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['admin_reply']) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
