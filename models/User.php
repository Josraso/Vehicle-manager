<?php
/**
 * Model User - Gestión de usuarios
 */

class User extends Model
{
    protected string $table = 'users';

    /**
     * Buscar por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Verificar si email existe
     */
    public function emailExists(string $email, int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crear usuario con hash de contraseña
     */
    public function createUser(string $name, string $email, string $password, string $role = 'user'): int
    {
        return $this->create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ]);
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Generar token de recuperación
     */
    public function generateResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->update($userId, [
            'reset_token' => $token,
            'reset_expires' => $expires
        ]);

        return $token;
    }

    /**
     * Buscar por token de recuperación
     */
    public function findByResetToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE reset_token = ? AND reset_expires > NOW()";
        return $this->queryOne($sql, [$token]);
    }

    /**
     * Limpiar token de recuperación
     */
    public function clearResetToken(int $userId): bool
    {
        return $this->update($userId, [
            'reset_token' => null,
            'reset_expires' => null
        ]);
    }

    /**
     * Actualizar tema
     */
    public function updateTheme(int $userId, string $theme): bool
    {
        return $this->update($userId, ['theme' => $theme]);
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verificar si es admin
     */
    public function isAdmin(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin';
    }

    /**
     * Obtener todos los usuarios (para admin)
     */
    public function getAllUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = 'u.role = ?';
            $params[] = $filters['role'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'u.is_active = ?';
            $params[] = $filters['is_active'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Contar total
        $countSql = "SELECT COUNT(*) FROM {$this->table} u {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Obtener usuarios con conteo de vehículos
        $sql = "SELECT u.id, u.name, u.email, u.role, u.is_active, u.email_notifications,
                       u.theme, u.last_login, u.created_at,
                       (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as vehicle_count
                FROM {$this->table} u
                {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $lastPage = (int) ceil($total / $perPage);
        if ($lastPage < 1) $lastPage = 1;

        return [
            'data' => $users,
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage
        ];
    }

    /**
     * Activar/Desactivar usuario
     */
    public function toggleActive(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        return $this->update($userId, [
            'is_active' => $user['is_active'] ? 0 : 1
        ]);
    }

    /**
     * Contar usuarios
     */
    public function countUsers(string $role = null): int
    {
        if ($role) {
            return $this->count('role', $role);
        }
        return $this->count();
    }

    /**
     * Contar usuarios activos
     */
    public function countActiveUsers(): int
    {
        return $this->count('is_active', 1);
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function getStats(): array
    {
        // Total usuarios
        $total = $this->count();

        // Usuarios activos
        $active = $this->count('is_active', 1);

        // Admins
        $admins = $this->count('role', 'admin');

        // Registros por mes (últimos 12 meses)
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month";
        $monthly = $this->query($sql);

        // Últimos logins
        $sql = "SELECT id, name, email, last_login
                FROM {$this->table}
                WHERE last_login IS NOT NULL
                ORDER BY last_login DESC
                LIMIT 10";
        $recentLogins = $this->query($sql);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'admins' => $admins,
            'users' => $total - $admins,
            'monthly_registrations' => $monthly,
            'recent_logins' => $recentLogins
        ];
    }

    /**
     * Obtener vehículos de un usuario (para admin)
     */
    public function getUserVehicles(int $userId): array
    {
        $sql = "SELECT v.id, v.brand, v.model, v.year, v.type, v.current_km,
                       v.license_plate as plate,
                       (SELECT COUNT(*) FROM fuel_logs WHERE vehicle_id = v.id) as fuel_count,
                       (SELECT COUNT(*) FROM maintenance_logs WHERE vehicle_id = v.id) as maintenance_count
                FROM vehicles v
                WHERE v.user_id = ?
                ORDER BY v.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Actualizar usuario (admin)
     */
    public function updateUser(int $userId, array $data): bool
    {
        $allowedFields = ['name', 'email', 'role', 'is_active', 'email_notifications'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->update($userId, $updateData);
    }
}
