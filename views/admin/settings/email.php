<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-envelope me-2"></i>Configuración de Email</h1>
            <a href="index.php?action=admin_dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver al Panel
            </a>
        </div>

        <!-- Navegación de configuración -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="index.php?action=admin_settings">
                    <i class="bi bi-gear me-1"></i>General
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="index.php?action=admin_email">
                    <i class="bi bi-envelope me-1"></i>Email
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?action=admin_templates">
                    <i class="bi bi-file-earmark-text me-1"></i>Plantillas
                </a>
            </li>
        </ul>

        <form method="POST" action="index.php?action=admin_email">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Driver de Email</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Método de Envío</label>
                        <select name="mail_driver" id="mail_driver" class="form-select">
                            <option value="mail" <?= $settings['mail_driver'] === 'mail' ? 'selected' : '' ?>>
                                PHP mail() - Función nativa
                            </option>
                            <option value="smtp" <?= $settings['mail_driver'] === 'smtp' ? 'selected' : '' ?>>
                                SMTP - Servidor externo
                            </option>
                            <option value="sendmail" <?= $settings['mail_driver'] === 'sendmail' ? 'selected' : '' ?>>
                                Sendmail - Servidor local
                            </option>
                        </select>
                        <div class="form-text">
                            <strong>mail():</strong> Usa la configuración del servidor PHP<br>
                            <strong>SMTP:</strong> Conexión directa a servidor de correo (Gmail, Outlook, etc.)<br>
                            <strong>Sendmail:</strong> Usa el programa sendmail del servidor
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" id="smtp_settings">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-server me-2"></i>Configuración SMTP</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Servidor SMTP</label>
                            <input type="text" name="mail_host" class="form-control"
                                   value="<?= htmlspecialchars($settings['mail_host']) ?>"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="mail_port" class="form-control"
                                   value="<?= (int) $settings['mail_port'] ?>"
                                   placeholder="587">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="mail_username" class="form-control"
                                   value="<?= htmlspecialchars($settings['mail_username']) ?>"
                                   placeholder="usuario@gmail.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="mail_password" class="form-control"
                                   placeholder="<?= !empty($settings['mail_password']) ? '••••••••' : 'Contraseña de aplicación' ?>">
                            <div class="form-text">Dejar vacío para mantener la actual</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cifrado</label>
                        <select name="mail_encryption" class="form-select">
                            <option value="tls" <?= $settings['mail_encryption'] === 'tls' ? 'selected' : '' ?>>TLS (Puerto 587)</option>
                            <option value="ssl" <?= $settings['mail_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL (Puerto 465)</option>
                            <option value="" <?= empty($settings['mail_encryption']) ? 'selected' : '' ?>>Sin cifrado</option>
                        </select>
                    </div>

                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-info-circle me-1"></i>Configuración para Gmail:</h6>
                        <ul class="mb-0 small">
                            <li>Servidor: <code>smtp.gmail.com</code></li>
                            <li>Puerto: <code>587</code> (TLS) o <code>465</code> (SSL)</li>
                            <li>Usa una <a href="https://myaccount.google.com/apppasswords" target="_blank">Contraseña de Aplicación</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Remitente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email del Remitente</label>
                            <input type="email" name="mail_from_address" class="form-control"
                                   value="<?= htmlspecialchars($settings['mail_from_address']) ?>"
                                   placeholder="noreply@tudominio.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Remitente</label>
                            <input type="text" name="mail_from_name" class="form-control"
                                   value="<?= htmlspecialchars($settings['mail_from_name']) ?>"
                                   placeholder="Vehicle Manager">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?action=admin_dashboard" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar Configuración
                </button>
            </div>
        </form>

        <!-- Probador de Email -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-send me-2"></i>Probar Configuración</h5>
            </div>
            <div class="card-body">
                <p>Envía un email de prueba para verificar que la configuración es correcta.</p>
                <form method="POST" action="index.php?action=admin_email_test" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="col-md-8">
                        <input type="email" name="test_email" class="form-control"
                               placeholder="Email de destino para la prueba" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-send me-1"></i>Enviar Prueba
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cola de Emails & Cron -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Cola de Emails (Queue)</h5>
            </div>
            <div class="card-body">
                <p>Los emails de recordatorios y notificaciones se añaden a una cola y se envían mediante un proceso cron automático en el servidor.</p>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="bi bi-key me-1"></i>Token del Cron</h6>
                                <div class="input-group">
                                    <?php $cronConfig = require __DIR__ . '/../../../config/app.php'; ?>
                                    <input type="text" class="form-control font-monospace" id="cronToken"
                                           value="<?= htmlspecialchars($cronConfig['cron']['token']) ?>"
                                           readonly style="font-size: 0.7rem;">
                                    <button class="btn btn-outline-secondary btn-sm" type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('cronToken').value).then(()=>alert('Token copiado'))">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Código secreto que protege el endpoint</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="bi bi-link-45deg me-1"></i>Endpoint URL</h6>
                                <div class="font-monospace text-break" style="font-size: 0.72rem;">
                                    <code>http://TUDOMINIO/index.php?action=cron_queue&amp;token=[TOKEN]</code>
                                </div>
                                <small class="text-muted">Reemplaza TUDOMINIO y [TOKEN]</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-dark text-light mb-3">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="bi bi-terminal me-1"></i>Configuración Crontab</h6>
                        <p class="small text-secondary mb-2">Ejecuta <code class="text-light">crontab -e</code> en tu servidor y añade esta línea:</p>
                        <div class="input-group">
                            <pre class="form-control bg-dark text-light border-secondary mb-0" id="cronCmd"
                                  style="font-size: 0.72rem; white-space: pre-wrap; resize: none; height: auto; padding: 8px;"
                                  readonly>* * * * * curl -s "http://TUDOMINIO/index.php?action=cron_queue&token=<?= htmlspecialchars($cronConfig['cron']['token']) ?>" > /dev/null 2>&1</pre>
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                    onclick="navigator.clipboard.writeText(document.getElementById('cronCmd').textContent.trim()).then(()=>alert('Comando copiado'))">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="text-secondary">Cambia <code class="text-light">TUDOMINIO</code> por tu dominio real. Se ejecuta cada minuto.</small>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>¿Cómo funciona?</strong> Cuando el sistema necesita enviar un email (recordatorio de mantenimiento, notificación), lo guarda en la cola de la base de datos. El cron llama a este endpoint cada minuto, que revisa si hay emails pendientes y los envía usando la configuración SMTP que has definido arriba. El token impide que terceros accedan al endpoint.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('mail_driver').addEventListener('change', function() {
    document.getElementById('smtp_settings').style.display =
        this.value === 'smtp' ? 'block' : 'none';
});
// Mostrar/ocultar al cargar
document.getElementById('smtp_settings').style.display =
    document.getElementById('mail_driver').value === 'smtp' ? 'block' : 'none';
</script>
