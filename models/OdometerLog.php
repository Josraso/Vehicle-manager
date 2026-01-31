<?php
/**
 * Model OdometerLog - Gestión de historial de kilometraje
 */

class OdometerLog extends Model
{
    protected string $table = 'odometer_logs';

    /**
     * Obtener historial de un vehículo
     */
    public function getByVehicle(int $vehicleId, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE vehicle_id = ?
                ORDER BY date DESC, id DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vehicleId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener registro con verificación de propiedad
     */
    public function getByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT o.*
                FROM {$this->table} o
                INNER JOIN vehicles v ON o.vehicle_id = v.id
                WHERE o.id = ? AND v.user_id = ?";
        return $this->queryOne($sql, [$id, $userId]);
    }

    /**
     * Crear registro de kilometraje
     */
    public function createLog(array $data): int
    {
        return $this->create([
            'vehicle_id' => $data['vehicle_id'],
            'km' => $data['km'],
            'date' => $data['date'],
            'source' => $data['source'] ?? 'manual',
            'source_id' => $data['source_id'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
    }

    /**
     * Registrar km desde repostaje
     */
    public function logFromFuel(int $vehicleId, int $km, string $date, int $fuelLogId): int
    {
        return $this->createLog([
            'vehicle_id' => $vehicleId,
            'km' => $km,
            'date' => $date,
            'source' => 'fuel',
            'source_id' => $fuelLogId
        ]);
    }

    /**
     * Registrar km desde mantenimiento
     */
    public function logFromMaintenance(int $vehicleId, int $km, string $date, int $maintLogId): int
    {
        return $this->createLog([
            'vehicle_id' => $vehicleId,
            'km' => $km,
            'date' => $date,
            'source' => 'maintenance',
            'source_id' => $maintLogId
        ]);
    }

    /**
     * Eliminar registros por fuente
     */
    public function deleteBySource(string $source, int $sourceId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE source = ? AND source_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$source, $sourceId]);
    }

    /**
     * Obtener evolución de km por mes
     */
    public function getMonthlyEvolution(int $vehicleId, int $year = null): array
    {
        $year = $year ?? date('Y');

        $sql = "SELECT
                MONTH(date) as month,
                MIN(km) as min_km,
                MAX(km) as max_km,
                MAX(km) - MIN(km) as km_driven
                FROM {$this->table}
                WHERE vehicle_id = ? AND YEAR(date) = ?
                GROUP BY MONTH(date)
                ORDER BY month";

        return $this->query($sql, [$vehicleId, $year]);
    }

    /**
     * Obtener km total recorrido en un período
     */
    public function getTotalKmDriven(int $vehicleId, string $startDate = null, string $endDate = null): int
    {
        $sql = "SELECT MAX(km) - MIN(km) as total FROM {$this->table} WHERE vehicle_id = ?";
        $params = [$vehicleId];

        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }

        $result = $this->queryOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Obtener último registro
     */
    public function getLastEntry(int $vehicleId): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE vehicle_id = ?
                ORDER BY km DESC
                LIMIT 1";
        return $this->queryOne($sql, [$vehicleId]);
    }
}
