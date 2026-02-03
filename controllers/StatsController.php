<?php
/**
 * Controller de Estadísticas
 */

class StatsController extends Controller
{
    private Vehicle $vehicleModel;
    private FuelLog $fuelModel;
    private MaintenanceLog $maintenanceModel;
    private OdometerLog $odometerModel;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
        $this->fuelModel = new FuelLog();
        $this->maintenanceModel = new MaintenanceLog();
        $this->odometerModel = new OdometerLog();
    }

    /**
     * Ver estadísticas de un vehículo
     */
    public function index(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $year = (int) $this->get('year', date('Y'));

        // Estadísticas generales
        $stats = $this->vehicleModel->getStats($vehicleId);
        $fuelStats = $this->fuelModel->getStats($vehicleId);
        $maintStats = $this->maintenanceModel->getStats($vehicleId);

        // Datos mensuales para gráficos
        $monthlyExpenses = $this->vehicleModel->getMonthlyExpenses($vehicleId, $year);
        $monthlyKm = $this->odometerModel->getMonthlyEvolution($vehicleId, $year);

        // Preparar datos para gráficos
        $chartData = $this->prepareChartData($monthlyExpenses, $monthlyKm);

        // Años disponibles para selector
        $availableYears = $this->getAvailableYears($vehicleId);

        // KM recorridos desde primer registro y coste por km
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(MIN(km), ?) as initial_km FROM (
            SELECT km FROM odometer_logs WHERE vehicle_id = ?
            UNION
            SELECT km FROM fuel_logs WHERE vehicle_id = ?
        ) AS all_km");
        $stmt->execute([$vehicle['current_km'], $vehicleId, $vehicleId]);
        $kmDriven = $vehicle['current_km'] - (int) $stmt->fetchColumn();
        $costPerKm = $kmDriven > 0 ? round($stats['total_cost'] / $kmDriven, 3) : 0;

        $this->render('stats/index', [
            'vehicle' => $vehicle,
            'stats' => $stats,
            'fuelStats' => $fuelStats,
            'maintStats' => $maintStats,
            'chartData' => $chartData,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'kmDriven' => $kmDriven,
            'costPerKm' => $costPerKm
        ]);
    }

    /**
     * Preparar datos para gráficos
     */
    private function prepareChartData(array $monthlyExpenses, array $monthlyKm): array
    {
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // Inicializar arrays con 0
        $fuelExpenses = array_fill(0, 12, 0);
        $maintExpenses = array_fill(0, 12, 0);
        $kmDriven = array_fill(0, 12, 0);

        // Llenar con datos reales
        foreach ($monthlyExpenses as $expense) {
            $monthIndex = $expense['month'] - 1;
            if ($expense['type'] === 'fuel') {
                $fuelExpenses[$monthIndex] = (float) $expense['amount'];
            } else {
                $maintExpenses[$monthIndex] = (float) $expense['amount'];
            }
        }

        foreach ($monthlyKm as $km) {
            $monthIndex = $km['month'] - 1;
            $kmDriven[$monthIndex] = (int) $km['km_driven'];
        }

        return [
            'labels' => $months,
            'fuel' => $fuelExpenses,
            'maintenance' => $maintExpenses,
            'km' => $kmDriven
        ];
    }

    /**
     * Obtener años con datos disponibles
     */
    private function getAvailableYears(int $vehicleId): array
    {
        $db = Database::getInstance();

        $sql = "SELECT DISTINCT YEAR(date) as year FROM (
                    SELECT date FROM fuel_logs WHERE vehicle_id = ?
                    UNION
                    SELECT date FROM maintenance_logs WHERE vehicle_id = ?
                    UNION
                    SELECT date FROM odometer_logs WHERE vehicle_id = ?
                ) AS dates
                ORDER BY year DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$vehicleId, $vehicleId, $vehicleId]);
        $results = $stmt->fetchAll();

        $years = array_column($results, 'year');

        // Asegurar que el año actual esté incluido
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }

    /**
     * Obtener datos para gráficos via AJAX
     */
    public function getData(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $year = (int) $this->get('year', date('Y'));

        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->json(['error' => 'Vehículo no encontrado'], 404);
            return;
        }

        $monthlyExpenses = $this->vehicleModel->getMonthlyExpenses($vehicleId, $year);
        $monthlyKm = $this->odometerModel->getMonthlyEvolution($vehicleId, $year);

        $chartData = $this->prepareChartData($monthlyExpenses, $monthlyKm);

        $this->json($chartData);
    }
}
