<?php
/**
 * Model FuelLog - Gestión de repostajes
 */

class FuelLog extends Model
{
    protected string $table = 'fuel_logs';

    /**
     * Obtener repostajes de un vehículo
     */
    public function getByVehicle(int $vehicleId, int $limit = 50): array
    {
        $sql = "SELECT f.*, ft.name as fuel_type_name
                FROM {$this->table} f
                LEFT JOIN fuel_types ft ON f.fuel_type_id = ft.id
                WHERE f.vehicle_id = ?
                ORDER BY f.date DESC, f.id DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vehicleId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener repostaje con verificación de propiedad
     */
    public function getByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT f.*, ft.name as fuel_type_name
                FROM {$this->table} f
                LEFT JOIN fuel_types ft ON f.fuel_type_id = ft.id
                INNER JOIN vehicles v ON f.vehicle_id = v.id
                WHERE f.id = ? AND v.user_id = ?";
        return $this->queryOne($sql, [$id, $userId]);
    }

    /**
     * Crear repostaje
     */
    public function createLog(array $data): int
    {
        // Calcular precio por litro
        $pricePerLiter = $data['liters'] > 0 ? $data['total_cost'] / $data['liters'] : 0;

        return $this->create([
            'vehicle_id' => $data['vehicle_id'],
            'fuel_type_id' => $data['fuel_type_id'] ?? 1,
            'date' => $data['date'],
            'km' => $data['km'],
            'liters' => $data['liters'],
            'price_per_liter' => round($pricePerLiter, 3),
            'total_cost' => $data['total_cost'],
            'full_tank' => $data['full_tank'] ?? 1,
            'notes' => $data['notes'] ?? null
        ]);
    }

    /**
     * Actualizar repostaje
     */
    public function updateLog(int $id, array $data): bool
    {
        $pricePerLiter = $data['liters'] > 0 ? $data['total_cost'] / $data['liters'] : 0;

        return $this->update($id, [
            'fuel_type_id' => $data['fuel_type_id'] ?? 1,
            'date' => $data['date'],
            'km' => $data['km'],
            'liters' => $data['liters'],
            'price_per_liter' => round($pricePerLiter, 3),
            'total_cost' => $data['total_cost'],
            'full_tank' => $data['full_tank'] ?? 1,
            'notes' => $data['notes'] ?? null
        ]);
    }

    /**
     * Obtener tipos de combustible
     */
    public function getFuelTypes(): array
    {
        $sql = "SELECT * FROM fuel_types ORDER BY id";
        return $this->query($sql);
    }

    /**
     * Calcular consumo entre dos repostajes
     */
    public function calculateConsumption(int $vehicleId): ?array
    {
        // Obtener últimos 2 repostajes con depósito lleno
        $sql = "SELECT km, liters FROM {$this->table}
                WHERE vehicle_id = ? AND full_tank = 1
                ORDER BY date DESC, id DESC
                LIMIT 2";
        $logs = $this->query($sql, [$vehicleId]);

        if (count($logs) < 2) {
            return null;
        }

        $kmDiff = $logs[0]['km'] - $logs[1]['km'];
        if ($kmDiff <= 0) {
            return null;
        }

        $consumption = ($logs[0]['liters'] / $kmDiff) * 100;

        return [
            'km_diff' => $kmDiff,
            'liters' => $logs[0]['liters'],
            'consumption' => round($consumption, 2)
        ];
    }

    /**
     * Obtener estadísticas de combustible
     */
    public function getStats(int $vehicleId): array
    {
        $sql = "SELECT
                COUNT(*) as count,
                COALESCE(SUM(total_cost), 0) as total_cost,
                COALESCE(SUM(liters), 0) as total_liters,
                COALESCE(AVG(price_per_liter), 0) as avg_price,
                COALESCE(MIN(price_per_liter), 0) as min_price,
                COALESCE(MAX(price_per_liter), 0) as max_price
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

        $sql = "SELECT COALESCE(SUM(total_cost), 0) as total
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

        $sql = "SELECT COALESCE(SUM(total_cost), 0) as total
                FROM {$this->table}
                WHERE vehicle_id = ? AND YEAR(date) = ?";

        $result = $this->queryOne($sql, [$vehicleId, $year]);
        return (float) $result['total'];
    }
}
