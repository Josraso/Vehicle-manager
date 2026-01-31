<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-pencil me-2"></i>Editar Plantilla</h1>
            <a href="index.php?action=admin_templates" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="index.php?action=admin_template_edit&id=<?= $template['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Información de la Plantilla</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Plantilla</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?= htmlspecialchars($template['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Identificador (Slug)</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($template['slug']) ?>" disabled>
                                <div class="form-text">El identificador no se puede modificar</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Asunto del Email</label>
                                <input type="text" name="subject" class="form-control"
                                       value="<?= htmlspecialchars($template['subject']) ?>" required>
                                <div class="form-text">Puedes usar variables como <code>{{user_name}}</code></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cuerpo del Email (HTML)</label>
                                <textarea name="body" class="form-control" rows="15" required><?= htmlspecialchars($template['body']) ?></textarea>
                                <div class="form-text">
                                    Puedes usar HTML y variables. Las variables se reemplazarán automáticamente.
                                </div>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Plantilla Activa</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php?action=admin_templates" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Variables Disponibles</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($variables)): ?>
                            <p class="small text-muted mb-2">Variables específicas de esta plantilla:</p>
                            <ul class="list-unstyled small">
                                <?php foreach ($variables as $var => $desc): ?>
                                    <li class="mb-1">
                                        <code>{{<?= htmlspecialchars($var) ?>}}</code>
                                        <br><span class="text-muted"><?= htmlspecialchars($desc) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                        <?php endif; ?>

                        <p class="small text-muted mb-2">Variables generales:</p>
                        <ul class="list-unstyled small">
                            <li><code>{{site_name}}</code> - Nombre del sitio</li>
                            <li><code>{{site_url}}</code> - URL del sitio</li>
                            <li><code>{{current_date}}</code> - Fecha actual</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-code me-2"></i>Ejemplo HTML</h6>
                    </div>
                    <div class="card-body">
                        <pre class="small mb-0" style="white-space: pre-wrap;"><code>&lt;h2&gt;Hola {{user_name}}&lt;/h2&gt;
&lt;p&gt;Te recordamos que...&lt;/p&gt;
&lt;p&gt;
  &lt;strong&gt;Vehículo:&lt;/strong&gt; {{vehicle_name}}&lt;br&gt;
  &lt;strong&gt;Matrícula:&lt;/strong&gt; {{vehicle_plate}}
&lt;/p&gt;
&lt;p&gt;Saludos,&lt;br&gt;{{site_name}}&lt;/p&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
