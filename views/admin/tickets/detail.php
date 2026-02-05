<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-headset me-2"></i>Ticket #<?= $ticket['id'] ?></h1>
    <div class="d-flex gap-2">
        <a href="index.php?action=admin_tickets" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <form method="POST" action="index.php?action=admin_ticket_close" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="id" value="<?= $ticket['id'] ?>">
            <button type="submit" class="btn btn-<?= $ticket['status'] === 'open' ? 'outline-success' : 'outline-warning' ?>">
                <i class="bi bi-<?= $ticket['status'] === 'open' ? 'check-circle' : 'arrow-counterclockwise' ?> me-1"></i>
                <?= $ticket['status'] === 'open' ? 'Cerrar' : 'Reabrir' ?>
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Ticket original -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars($ticket['subject']) ?></h5>
                <span class="badge bg-<?= $ticket['status'] === 'open' ? 'warning text-dark' : 'success' ?>">
                    <?= $ticket['status'] === 'open' ? 'Abierto' : 'Cerrado' ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-person-circle fs-5 me-2 text-muted"></i>
                    <div>
                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                        <span class="text-muted ms-2"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>
                <p class="border-start ps-3 text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['message']) ?></p>
                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></small>
            </div>
        </div>

        <!-- Respuesta del admin (si existe) -->
        <?php if ($ticket['admin_reply']): ?>
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-reply me-1"></i>Tu respuesta</span>
                <small class="opacity-75"><?= date('d/m/Y H:i', strtotime($ticket['replied_at'])) ?></small>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['admin_reply']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulario de respuesta -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-reply me-2"></i>Responder</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?action=admin_ticket_reply">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="id" value="<?= $ticket['id'] ?>">

                    <div class="mb-3">
                        <textarea name="reply" class="form-control" rows="4"
                                  placeholder="Escribe tu respuesta..." required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="close" value="0" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Responder
                        </button>
                        <?php if ($ticket['status'] === 'open'): ?>
                        <button type="submit" name="close" value="1" class="btn btn-success">
                            <i class="bi bi-send-check me-1"></i>Responder y Cerrar
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar: info usuario -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Usuario</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Nombre:</td>
                        <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td>
                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                <?= htmlspecialchars($user['email']) ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Rol:</td>
                        <td>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                <?= $user['role'] === 'admin' ? 'Admin' : 'Usuario' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Registro:</td>
                        <td><small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Vehículos:</td>
                        <td><strong><?= $user['vehicle_count'] ?? '—' ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
