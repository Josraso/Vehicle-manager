<?php
/**
 * Model EmailTemplate - Plantillas de email
 */

class EmailTemplate extends Model
{
    protected string $table = 'email_templates';

    /**
     * Buscar por slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Obtener todas las plantillas activas
     */
    public function getActive(): array
    {
        return $this->findAllBy('is_active', 1, 'name', 'ASC');
    }

    /**
     * Actualizar plantilla
     */
    public function updateTemplate(int $id, array $data): bool
    {
        return $this->update($id, [
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'is_active' => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Obtener variables de una plantilla
     */
    public function getVariables(string $slug): array
    {
        $template = $this->findBySlug($slug);

        if (!$template || !$template['variables']) {
            return [];
        }

        return json_decode($template['variables'], true) ?? [];
    }

    /**
     * Renderizar plantilla con variables
     */
    public function render(string $slug, array $variables = []): ?array
    {
        $template = $this->findBySlug($slug);

        if (!$template) {
            return null;
        }

        $subject = $template['subject'];
        $body = $template['body'];

        // Reemplazar variables
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $body = str_replace('{' . $key . '}', $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Activar/Desactivar plantilla
     */
    public function toggleActive(int $id): bool
    {
        $template = $this->find($id);
        if (!$template) {
            return false;
        }

        return $this->update($id, [
            'is_active' => $template['is_active'] ? 0 : 1
        ]);
    }
}
