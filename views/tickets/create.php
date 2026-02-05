<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-headset me-2"></i>Nuevo Ticket</h1>
            <a href="index.php?action=tickets" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Mis Tickets
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-envelope-plus me-2"></i>Contactar Soporte</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?action=ticket_create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-3">
                        <label class="form-label">Asunto *</label>
                        <input type="text" name="subject" class="form-control"
                               placeholder="Describe brevemente tu problema" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensaje *</label>
                        <textarea name="message" class="form-control" rows="5"
                                  placeholder="Describe tu problema con el máximo detalle posible..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=tickets" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Enviar Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
