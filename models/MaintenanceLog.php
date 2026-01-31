<?php
/**
 * Model MaintenanceLog - Gestión de mantenimientos
 */

class MaintenanceLog extends Model
{
    protected string $table = 'maintenance_logs';

    /**
     * Obtener mantenimientos de un vehículo
     */
    public function getByVehicle(int $vehicleId, int $limit = 50): array
    {
        $sql = "SELECT m.*, mt.name as type_name, mt.default_km_interval, mt.default_days_interval
                FROM {$this->table} m
                LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                WHERE m.vehicle_id = ?
                ORDER BY m.date DESC, m.id DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vehicleId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener mantenimiento con verificación de propiedad
     */
    public function getByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT m.*, mt.name as type_name
                FROM {$this->table} m
                LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                INNER JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.id = ? AND v.user_id = ?";
        return $this->queryOne($sql, [$id, $userId]);
    }

    /**
     * Crear mantenimiento
     */
    public function createLog(array $data): int
    {
        // Usar valores personalizados si se proporcionan, o calcular basado en el tipo
        $nextKm = isset($data['next_km']) ? $data['next_km'] : null;
        $nextDate = isset($data['next_date']) ? $data['next_date'] : null;

        // Si no hay valores personalizados, calcular basado en el tipo de mantenimiento
        if ($nextKm === null && $nextDate === null && isset($data['maintenance_type_id'])) {
            $type = $this->getMaintenanceType($data['maintenance_type_id']);
            if ($type) {
                if ($type['default_km_interval']) {
                    $nextKm = $data['km'] + $type['default_km_interval'];
                }
                if ($type['default_days_interval']) {
                    $nextDate = date('Y-m-d', strtotime($data['date'] . ' + ' . $type['default_days_interval'] . ' days'));
                }
            }
        }

        return $this->create([
            'vehicle_id' => $data['vehicle_id'],
            'maintenance_type_id' => $data['maintenance_type_id'] ?? null,
            'date' => $data['date'],
            'km' => $data['km'],
            'cost' => $data['cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'next_km' => $nextKm,
            'next_date' => $nextDate
        ]);
    }

    /**
     * Actualizar mantenimiento
     */
    public function updateLog(int $id, array $data): bool
    {
        // Usar valores personalizados si se proporcionan, o calcular basado en el tipo
        $nextKm = isset($data['next_km']) ? $data['next_km'] : null;
        $nextDate = isset($data['next_date']) ? $data['next_date'] : null;

        // Si no hay valores personalizados, calcular basado en el tipo de mantenimiento
        if ($nextKm === null && $nextDate === null && isset($data['maintenance_type_id'])) {
            $type = $this->getMaintenanceType($data['maintenance_type_id']);
            if ($type) {
                if ($type['default_km_interval']) {
                    $nextKm = $data['km'] + $type['default_km_interval'];
                }
                if ($type['default_days_interval']) {
                    $nextDate = date('Y-m-d', strtotime($data['date'] . ' + ' . $type['default_days_interval'] . ' days'));
                }
            }
        }

        return $this->update($id, [
            'maintenance_type_id' => $data['maintenance_type_id'] ?? null,
            'date' => $data['date'],
            'km' => $data['km'],
            'cost' => $data['cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'next_km' => $nextKm,
            'next_date' => $nextDate
        ]);
    }

    /**
     * Obtener tipos de mantenimiento
     */
    public function getMaintenanceTypes(): array
    {
        $sql = "SELECT * FROM maintenance_types ORDER BY id";
        return $this->query($sql);
    }

    /**
     * Obtener tipo de mantenimiento por ID
     */
    public function getMaintenanceType(int $id): ?array
    {
        $sql = "SELECT * FROM maintenance_types WHERE id = ?";
        return $this->queryOne($sql, [$id]);
    }

    /**
     * Obtener recordatorios pendientes
     */
    public function getPendingReminders(int $vehicleId, int $currentKm): array
    {
        $today = date('Y-m-d');
        $reminderDate = date('Y-m-d', strtotime('+7 days'));

        $sql = "SELECT m.*, mt.name as type_name
                FROM {$this->table} m
                LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                WHERE m.vehicle_id = ?
                AND (
                    (m.next_km IS NOT NULL AND m.next_km <= ?)
                    OR (m.next_date IS NOT NULL AND m.next_date <= ?)
                )
                ORDER BY COALESCE(m.next_date, '9999-12-31'), m.next_km";

        return $this->query($sql, [$vehicleId, $currentKm + 500, $reminderDate]);
    }

    /**
     * Obtener estadísticas de mantenimiento
     */
    public function getStats(int $vehicleId): array
    {
        $sql = "SELECT
                COUNT(*) as count,
                COALESCE(SUM(cost), 0) as total_cost,
                COALESCE(AVG(cost), 0) as avg_cost
                FROM {$this->table}
                WHERE vehicle_id = ?";

        return $this->queryOne($sql, [$vehicleId]);
    }

    /**
     * Obtener gasto mensual
     */
    public function getMonthlyTotal(int $vehicleId, int $month = null, int $year = null): float
    {
        $month = $month ?? date('n');
        $year = $year ?? date('Y');

        $sql = "SELECT COALESCE(SUM(cost), 0) as total
                FROM {$this->table}
                WHERE vehicle_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";

        $result = $this->queryOne($sql, [$vehicleId, $month, $year]);
        return (float) $result['total'];
    }

    /**
     * Obtener gasto anual
     */
    public function getYearlyTotal(int $vehicleId, int $year = null): float
    {
        $year = $year ?? date('Y');

        $sql = "SELECT COALESCE(SUM(cost), 0) as total
                FROM {$this->table}
                WHERE vehicle_id = ? AND YEAR(date) = ?";

        $result = $this->queryOne($sql, [$vehicleId, $year]);
        return (float) $result['total'];
    }

    /**
     * Obtener últimos mantenimientos por tipo
     */
    public function getLastByType(int $vehicleId): array
    {
        $sql = "SELECT m.*, mt.name as type_name,
                mt.default_km_interval, mt.default_days_interval
                FROM {$this->table} m
                INNER JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
                WHERE m.vehicle_id = ?
                AND m.id = (
                    SELECT MAX(m2.id) FROM {$this->table} m2
                    WHERE m2.vehicle_id = m.vehicle_id
                    AND m2.maintenance_type_id = m.maintenance_type_id
                )
                ORDER BY mt.name";

        return $this->query($sql, [$vehicleId]);
    }
}
