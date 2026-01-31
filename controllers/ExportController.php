<?php
/**
 * Controller de Exportación - CSV y PDF
 */

class ExportController extends Controller
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
     * Exportar repostajes a CSV
     */
    public function fuelCsv(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $logs = $this->fuelModel->getByVehicle($vehicleId, 9999);

        $filename = "repostajes_{$vehicle['brand']}_{$vehicle['model']}_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM para Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        fputcsv($output, [
            'Fecha',
            'Kilometraje',
            'Litros',
            'Precio/Litro',
            'Coste Total',
            'Tipo Combustible',
            'Depósito Lleno',
            'Notas'
        ], ';');

        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['date'],
                $log['km'],
                number_format($log['liters'], 2, ',', ''),
                number_format($log['price_per_liter'], 3, ',', ''),
                number_format($log['total_cost'], 2, ',', ''),
                $log['fuel_type_name'] ?? 'N/A',
                $log['full_tank'] ? 'Sí' : 'No',
                $log['notes'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar mantenimientos a CSV
     */
    public function maintenanceCsv(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $logs = $this->maintenanceModel->getByVehicle($vehicleId, 9999);

        $filename = "mantenimientos_{$vehicle['brand']}_{$vehicle['model']}_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM para Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        fputcsv($output, [
            'Fecha',
            'Tipo',
            'Kilometraje',
            'Coste',
            'Notas',
            'Próximo KM',
            'Próxima Fecha'
        ], ';');

        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['date'],
                $log['type_name'] ?? 'N/A',
                $log['km'],
                number_format($log['cost'], 2, ',', ''),
                $log['notes'] ?? '',
                $log['next_km'] ?? '',
                $log['next_date'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar todo a CSV (backup)
     */
    public function backupCsv(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $fuelLogs = $this->fuelModel->getByVehicle($vehicleId, 9999);
        $maintLogs = $this->maintenanceModel->getByVehicle($vehicleId, 9999);
        $odomLogs = $this->odometerModel->getByVehicle($vehicleId, 9999);

        $filename = "backup_{$vehicle['brand']}_{$vehicle['model']}_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Info del vehículo
        fputcsv($output, ['=== DATOS DEL VEHÍCULO ==='], ';');
        fputcsv($output, ['Tipo', 'Marca', 'Modelo', 'Año', 'Cilindrada', 'Matrícula', 'KM Actual'], ';');
        fputcsv($output, [
            $vehicle['type'],
            $vehicle['brand'],
            $vehicle['model'],
            $vehicle['year'],
            $vehicle['displacement'] ?? 'N/A',
            $vehicle['license_plate'],
            $vehicle['current_km']
        ], ';');

        fputcsv($output, [''], ';');
        fputcsv($output, ['=== REPOSTAJES ==='], ';');
        fputcsv($output, ['Fecha', 'KM', 'Litros', '€/L', 'Total', 'Tipo', 'Lleno', 'Notas'], ';');
        foreach ($fuelLogs as $log) {
            fputcsv($output, [
                $log['date'],
                $log['km'],
                number_format($log['liters'], 2, ',', ''),
                number_format($log['price_per_liter'], 3, ',', ''),
                number_format($log['total_cost'], 2, ',', ''),
                $log['fuel_type_name'] ?? '',
                $log['full_tank'] ? 'Sí' : 'No',
                $log['notes'] ?? ''
            ], ';');
        }

        fputcsv($output, [''], ';');
        fputcsv($output, ['=== MANTENIMIENTOS ==='], ';');
        fputcsv($output, ['Fecha', 'Tipo', 'KM', 'Coste', 'Notas'], ';');
        foreach ($maintLogs as $log) {
            fputcsv($output, [
                $log['date'],
                $log['type_name'] ?? '',
                $log['km'],
                number_format($log['cost'], 2, ',', ''),
                $log['notes'] ?? ''
            ], ';');
        }

        fputcsv($output, [''], ';');
        fputcsv($output, ['=== HISTORIAL KILOMETRAJE ==='], ';');
        fputcsv($output, ['Fecha', 'KM', 'Origen', 'Notas'], ';');
        foreach ($odomLogs as $log) {
            fputcsv($output, [
                $log['date'],
                $log['km'],
                $log['source'],
                $log['notes'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar a PDF (HTML imprimible)
     */
    public function pdf(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $type = $this->get('type', 'all');

        $fuelLogs = [];
        $maintLogs = [];
        $stats = null;

        if ($type === 'all' || $type === 'fuel') {
            $fuelLogs = $this->fuelModel->getByVehicle($vehicleId, 9999);
        }

        if ($type === 'all' || $type === 'maintenance') {
            $maintLogs = $this->maintenanceModel->getByVehicle($vehicleId, 9999);
        }

        if ($type === 'all' || $type === 'stats') {
            $stats = $this->vehicleModel->getStats($vehicleId);
        }

        // Renderizar vista de impresión
        require __DIR__ . '/../views/export/pdf.php';
        exit;
    }
}
