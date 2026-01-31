<?php
/**
 * Model EmailQueue - Cola de emails pendientes
 */

class EmailQueue extends Model
{
    protected string $table = 'email_queue';

    /**
     * Añadir email a la cola
     */
    public function enqueue(string $toEmail, string $toName, string $subject, string $body, ?string $scheduledAt = null): int
    {
        return $this->create([
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => $scheduledAt ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener emails pendientes de envío
     */
    public function getPending(int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'pending' AND attempts < 3
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY created_at ASC LIMIT ?";
        return $this->query($sql, [$limit]);
    }

    /**
     * Marcar email como enviado
     */
    public function markSent(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'sent', sent_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Marcar email como fallido
     */
    public function markFailed(int $id, string $error): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'failed', error_message = ?, attempts = attempts + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$error, $id]);
    }

    /**
     * Limpiar emails antiguos (enviados o fallidos)
     */
    public function cleanOld(int $days = 30): int
    {
        $sql = "DELETE FROM {$this->table} WHERE status IN ('sent', 'failed') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * Obtener estadísticas de la cola
     */
    public function getStats(): array
    {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status ORDER BY status";
        return $this->query($sql);
    }
}
