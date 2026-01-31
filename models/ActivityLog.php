<?php
/**
 * Model ActivityLog - Registro de actividad
 */

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';

    /**
     * Registrar actividad
     */
    public function log(string $action, string $description, ?string $entityType = null, ?int $entityId = null): int
    {
        return $this->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $this->getClientIp(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    }

    /**
     * Obtener logs recientes
     */
    public function getRecent(int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name, u.email as user_email
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener logs de un usuario
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener logs por acción
     */
    public function getByAction(string $action, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.action = ?
                ORDER BY al.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$action, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener logs filtrados con paginación
     */
    public function getFiltered(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'al.action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(al.created_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(al.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Contar total
        $countSql = "SELECT COUNT(*) FROM {$this->table} al {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Obtener registros
        $sql = "SELECT al.*, u.name as user_name, u.email as user_email
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $lastPage = (int) ceil($total / $perPage);
        if ($lastPage < 1) $lastPage = 1;

        return [
            'data' => $logs,
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage
        ];
    }

    /**
     * Obtener acciones únicas
     */
    public function getUniqueActions(): array
    {
        $sql = "SELECT DISTINCT action FROM {$this->table} ORDER BY action";
        return array_column($this->query($sql), 'action');
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs(int $days = 90): int
    {
        if ($days <= 0) {
            $sql = "DELETE FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
        }
        return $stmt->rowCount();
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): ?string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Obtener estadísticas de actividad
     */
    public function getStats(): array
    {
        // Actividad por día (últimos 30 días)
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date";
        $daily = $this->query($sql);

        // Top acciones
        $sql = "SELECT action, COUNT(*) as count
                FROM {$this->table}
                GROUP BY action
                ORDER BY count DESC
                LIMIT 10";
        $topActions = $this->query($sql);

        // Usuarios más activos
        $sql = "SELECT al.user_id, u.name, u.email, COUNT(*) as count
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.user_id IS NOT NULL
                GROUP BY al.user_id
                ORDER BY count DESC
                LIMIT 10";
        $topUsers = $this->query($sql);

        return [
            'daily' => $daily,
            'top_actions' => $topActions,
            'top_users' => $topUsers,
            'total' => $this->count()
        ];
    }
}
