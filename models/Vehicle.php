<?php
/**
 * Model Vehicle - Gestión de vehículos
 */

class Vehicle extends Model
{
    protected string $table = 'vehicles';

    /**
     * Obtener vehículos del usuario
     */
    public function getByUser(int $userId): array
    {
        return $this->findAllBy('user_id', $userId, 'created_at', 'DESC');
    }

    /**
     * Obtener vehículo del usuario por ID
     */
    public function getByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
        return $this->queryOne($sql, [$id, $userId]);
    }

    /**
     * Crear vehículo
     */
    public function createVehicle(int $userId, array $data): int
    {
        return $this->create([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'motorcycle',
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'displacement' => $data['displacement'] ?? null,
            'license_plate' => $data['license_plate'],
            'photo' => $data['photo'] ?? null,
            'current_km' => $data['current_km'] ?? 0
        ]);
    }

    /**
     * Actualizar kilometraje
     */
    public function updateKm(int $vehicleId, int $km): bool
    {
        $sql = "UPDATE {$this->table} SET current_km = ? WHERE id = ? AND current_km < ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$km, $vehicleId, $km]);
    }

    /**
     * Obtener estadísticas del vehículo
     */
    public function getStats(int $vehicleId): array
    {
        // Total gastado en combustible
        $sqlFuel = "SELECT
            COUNT(*) as total_refuels,
            COALESCE(SUM(total_cost), 0) as total_fuel_cost,
            COALESCE(SUM(liters), 0) as total_liters,
            COALESCE(AVG(price_per_liter), 0) as avg_price_liter
            FROM fuel_logs WHERE vehicle_id = ?";
        $fuelStats = $this->queryOne($sqlFuel, [$vehicleId]);

        // Total gastado en mantenimiento
        $sqlMaint = "SELECT
            COUNT(*) as total_maintenances,
            COALESCE(SUM(cost), 0) as total_maint_cost
            FROM maintenance_logs WHERE vehicle_id = ?";
        $maintStats = $this->queryOne($sqlMaint, [$vehicleId]);

        // Consumo medio (L/100km) - excluir litros del primer llenado (baseline)
        $sqlConsumption = "SELECT
            SUM(liters) as total_liters,
            MAX(km) - MIN(km) as km_range,
            (SELECT liters FROM fuel_logs WHERE vehicle_id = ? AND full_tank = 1 ORDER BY km ASC, id ASC LIMIT 1) as first_fill_liters
            FROM fuel_logs
            WHERE vehicle_id = ? AND full_tank = 1";
        $consumptionData = $this->queryOne($sqlConsumption, [$vehicleId, $vehicleId]);

        $avgConsumption = 0;
        if ($consumptionData && $consumptionData['km_range'] > 0 && $consumptionData['first_fill_liters'] !== null) {
            $consumedLiters = $consumptionData['total_liters'] - $consumptionData['first_fill_liters'];
            if ($consumedLiters > 0) {
                $avgConsumption = ($consumedLiters / $consumptionData['km_range']) * 100;
            }
        }

        return [
            'total_refuels' => (int) $fuelStats['total_refuels'],
            'total_fuel_cost' => (float) $fuelStats['total_fuel_cost'],
            'total_liters' => (float) $fuelStats['total_liters'],
            'avg_price_liter' => (float) $fuelStats['avg_price_liter'],
            'total_maintenances' => (int) $maintStats['total_maintenances'],
            'total_maint_cost' => (float) $maintStats['total_maint_cost'],
            'total_cost' => (float) $fuelStats['total_fuel_cost'] + (float) $maintStats['total_maint_cost'],
            'avg_consumption' => round($avgConsumption, 2)
        ];
    }

    /**
     * Obtener gastos mensuales
     */
    public function getMonthlyExpenses(int $vehicleId, int $year = null): array
    {
        $year = $year ?? date('Y');

        $sql = "SELECT
            MONTH(date) as month,
            'fuel' as type,
            SUM(total_cost) as amount
            FROM fuel_logs
            WHERE vehicle_id = ? AND YEAR(date) = ?
            GROUP BY MONTH(date)

            UNION ALL

            SELECT
            MONTH(date) as month,
            'maintenance' as type,
            SUM(cost) as amount
            FROM maintenance_logs
            WHERE vehicle_id = ? AND YEAR(date) = ?
            GROUP BY MONTH(date)

            ORDER BY month, type";

        return $this->query($sql, [$vehicleId, $year, $vehicleId, $year]);
    }

    /**
     * Obtener km recorridos por mes
     */
    public function getMonthlyKm(int $vehicleId, int $year = null): array
    {
        $year = $year ?? date('Y');

        $sql = "SELECT
            MONTH(date) as month,
            MAX(km) - MIN(km) as km_driven
            FROM odometer_logs
            WHERE vehicle_id = ? AND YEAR(date) = ?
            GROUP BY MONTH(date)
            ORDER BY month";

        return $this->query($sql, [$vehicleId, $year]);
    }
}
