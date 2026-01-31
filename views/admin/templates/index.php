<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-file-earmark-text me-2"></i>Plantillas de Email</h1>
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
                <a class="nav-link" href="index.php?action=admin_email">
                    <i class="bi bi-envelope me-1"></i>Email
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="index.php?action=admin_templates">
                    <i class="bi bi-file-earmark-text me-1"></i>Plantillas
                </a>
            </li>
        </ul>

        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            Las plantillas de email utilizan variables entre llaves que serán reemplazadas automáticamente.
            Por ejemplo: <code>{{user_name}}</code>, <code>{{vehicle_name}}</code>, <code>{{maintenance_type}}</code>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($templates)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-file-earmark-x fs-1"></i>
                        <p class="mt-2">No hay plantillas configuradas</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Identificador</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($template['name']) ?></strong>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($template['slug']) ?></code>
                                        </td>
                                        <td><?= htmlspecialchars($template['subject']) ?></td>
                                        <td>
                                            <?php if ($template['is_active']): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="index.php?action=admin_template_edit&id=<?= $template['id'] ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil me-1"></i>Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ayuda sobre variables -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-code me-2"></i>Variables Disponibles</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>Usuario</h6>
                        <ul class="list-unstyled small">
                            <li><code>{{user_name}}</code> - Nombre del usuario</li>
                            <li><code>{{user_email}}</code> - Email del usuario</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6>Vehículo</h6>
                        <ul class="list-unstyled small">
                            <li><code>{{vehicle_name}}</code> - Marca y modelo</li>
                            <li><code>{{vehicle_plate}}</code> - Matrícula</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6>Mantenimiento</h6>
                        <ul class="list-unstyled small">
                            <li><code>{{maintenance_type}}</code> - Tipo de mantenimiento</li>
                            <li><code>{{due_date}}</code> - Fecha de vencimiento</li>
                            <li><code>{{due_km}}</code> - Kilómetros de vencimiento</li>
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <h6>Sistema</h6>
                        <ul class="list-unstyled small">
                            <li><code>{{site_name}}</code> - Nombre del sitio</li>
                            <li><code>{{site_url}}</code> - URL del sitio</li>
                            <li><code>{{current_date}}</code> - Fecha actual</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6>Recuperación de Contraseña</h6>
                        <ul class="list-unstyled small">
                            <li><code>{{reset_link}}</code> - Enlace de recuperación</li>
                            <li><code>{{reset_token}}</code> - Token de recuperación</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
