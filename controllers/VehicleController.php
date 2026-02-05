<?php
/**
 * Controller de Vehículos - CRUD completo
 */

class VehicleController extends Controller
{
    private Vehicle $vehicleModel;
    private FuelLog $fuelModel;
    private MaintenanceLog $maintenanceModel;
    private OdometerLog $odometerModel;
    private array $fuelTypes;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
        $this->fuelModel = new FuelLog();
        $this->maintenanceModel = new MaintenanceLog();
        $this->odometerModel = new OdometerLog();
        $this->fuelTypes = $this->fuelModel->getFuelTypes();
    }

    /**
     * Dashboard - Listado de vehículos
     */
    public function index(): void
    {
        Auth::require();

        $vehicles = $this->vehicleModel->getByUser(Auth::id());

        // Agregar estadísticas básicas a cada vehículo
        foreach ($vehicles as &$vehicle) {
            $vehicle['stats'] = $this->vehicleModel->getStats($vehicle['id']);
            $vehicle['reminders'] = $this->maintenanceModel->getPendingReminders(
                $vehicle['id'],
                $vehicle['current_km']
            );
        }

        // Estadísticas globales para el resumen del dashboard
        $globalStats = $this->vehicleModel->getGlobalStats(Auth::id());
        $totalReminders = 0;
        foreach ($vehicles as $v) {
            $totalReminders += count($v['reminders']);
        }

        $this->render('vehicles/index', [
            'vehicles' => $vehicles,
            'globalStats' => $globalStats,
            'totalReminders' => $totalReminders,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Comparativa entre vehículos del usuario
     */
    public function compare(): void
    {
        Auth::require();

        $vehicles = $this->vehicleModel->getByUser(Auth::id());

        if (count($vehicles) < 2) {
            $this->flash('info', 'Necesitas al menos 2 vehículos para comparar');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $db = Database::getInstance();

        foreach ($vehicles as &$vehicle) {
            $vehicle['stats'] = $this->vehicleModel->getStats($vehicle['id']);
            $vehicle['consumption'] = $this->fuelModel->calculateConsumption($vehicle['id']);

            // km recorridos desde primer registro
            $stmt = $db->prepare("SELECT COALESCE(MIN(km), ?) as initial_km FROM (
                SELECT km FROM odometer_logs WHERE vehicle_id = ?
                UNION
                SELECT km FROM fuel_logs WHERE vehicle_id = ?
            ) AS all_km");
            $stmt->execute([$vehicle['current_km'], $vehicle['id'], $vehicle['id']]);
            $vehicle['km_driven'] = $vehicle['current_km'] - (int) $stmt->fetchColumn();
            $vehicle['cost_per_km'] = $vehicle['km_driven'] > 0
                ? round($vehicle['stats']['total_cost'] / $vehicle['km_driven'], 3) : 0;

            $vehicle['yearly_fuel'] = $this->fuelModel->getYearlyTotal($vehicle['id']);
            $vehicle['yearly_maint'] = $this->maintenanceModel->getYearlyTotal($vehicle['id']);
        }
        unset($vehicle);

        $this->render('vehicles/compare', [
            'vehicles' => $vehicles,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Ver detalle de vehículo
     */
    public function show(): void
    {
        Auth::require();

        $id = (int) $this->get('id');
        $vehicle = $this->vehicleModel->getByIdAndUser($id, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $stats = $this->vehicleModel->getStats($id);
        $fuelLogs = $this->fuelModel->getByVehicle($id);
        $maintenanceLogs = $this->maintenanceModel->getByVehicle($id);
        $odometerLogs = $this->odometerModel->getByVehicle($id);
        $reminders = $this->maintenanceModel->getPendingReminders($id, $vehicle['current_km']);

        // Calcular consumo actual
        $consumption = $this->fuelModel->calculateConsumption($id);

        // Gastos del mes actual
        $monthlyFuel = $this->fuelModel->getMonthlyTotal($id);
        $monthlyMaint = $this->maintenanceModel->getMonthlyTotal($id);

        // Gastos del año
        $yearlyFuel = $this->fuelModel->getYearlyTotal($id);
        $yearlyMaint = $this->maintenanceModel->getYearlyTotal($id);

        // KM recorridos desde el primer registro y coste por km
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(MIN(km), ?) as initial_km FROM (
            SELECT km FROM odometer_logs WHERE vehicle_id = ?
            UNION
            SELECT km FROM fuel_logs WHERE vehicle_id = ?
        ) AS all_km");
        $stmt->execute([$vehicle['current_km'], $id, $id]);
        $kmDriven = $vehicle['current_km'] - (int) $stmt->fetchColumn();
        $costPerKm = $kmDriven > 0 ? round($stats['total_cost'] / $kmDriven, 3) : 0;

        // Consumo por repostaje lleno (entre llenados consecutivos)
        $lastFullFillKm = null;
        for ($i = count($fuelLogs) - 1; $i >= 0; $i--) {
            $fuelLogs[$i]['row_consumption'] = null;
            if ($fuelLogs[$i]['full_tank']) {
                if ($lastFullFillKm !== null) {
                    $kmDiff = $fuelLogs[$i]['km'] - $lastFullFillKm;
                    if ($kmDiff > 0) {
                        $fuelLogs[$i]['row_consumption'] = round(($fuelLogs[$i]['liters'] / $kmDiff) * 100, 1);
                    }
                }
                $lastFullFillKm = $fuelLogs[$i]['km'];
            }
        }

        $this->render('vehicles/show', [
            'vehicle' => $vehicle,
            'stats' => $stats,
            'fuelLogs' => $fuelLogs,
            'maintenanceLogs' => $maintenanceLogs,
            'odometerLogs' => $odometerLogs,
            'reminders' => $reminders,
            'consumption' => $consumption,
            'monthlyFuel' => $monthlyFuel,
            'monthlyMaint' => $monthlyMaint,
            'yearlyFuel' => $yearlyFuel,
            'yearlyMaint' => $yearlyMaint,
            'costPerKm' => $costPerKm,
            'kmDriven' => $kmDriven,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Formulario crear vehículo
     */
    public function create(): void
    {
        Auth::require();

        if ($this->isPost()) {
            $this->store();
            return;
        }

        $this->render('vehicles/create', [
            'fuelTypes' => $this->fuelTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Guardar nuevo vehículo
     */
    private function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=vehicle_create');
            return;
        }

        $data = [
            'type' => $this->post('type', 'motorcycle'),
            'brand' => trim($this->post('brand', '')),
            'model' => trim($this->post('model', '')),
            'year' => (int) $this->post('year', date('Y')),
            'displacement' => $this->post('displacement') ? (int) $this->post('displacement') : null,
            'license_plate' => strtoupper(trim($this->post('license_plate', ''))),
            'current_km' => (int) $this->post('current_km', 0),
            'default_fuel_type_id' => (int) $this->post('default_fuel_type_id', 1)
        ];

        $validator = new Validator($data);
        $validator
            ->required('brand', 'La marca es obligatoria')
            ->required('model', 'El modelo es obligatorio')
            ->required('year', 'El año es obligatorio')
            ->year('year')
            ->required('license_plate', 'La matrícula es obligatoria')
            ->minValue('current_km', 0, 'El kilometraje no puede ser negativo');

        if ($validator->fails()) {
            $this->render('vehicles/create', [
                'error' => $validator->firstError(),
                'data' => $data,
                'fuelTypes' => $this->fuelTypes,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Procesar foto si se subió
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->uploadPhoto($_FILES['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }

        $vehicleId = $this->vehicleModel->createVehicle(Auth::id(), $data);

        if ($vehicleId) {
            // Registrar km inicial en odómetro si se proporcionó
            if ($data['current_km'] > 0) {
                $odometerLog = new OdometerLog();
                $odometerLog->createLog([
                    'vehicle_id' => $vehicleId,
                    'km' => $data['current_km'],
                    'date' => date('Y-m-d'),
                    'source' => 'manual',
                    'notes' => 'Kilometraje inicial al crear el vehículo'
                ]);
            }

            $this->flash('success', 'Vehículo añadido correctamente');
            $this->redirect('index.php?action=vehicle_show&id=' . $vehicleId);
        } else {
            $this->render('vehicles/create', [
                'error' => 'Error al crear el vehículo',
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
        }
    }

    /**
     * Formulario editar vehículo
     */
    public function edit(): void
    {
        Auth::require();

        $id = (int) $this->get('id');
        $vehicle = $this->vehicleModel->getByIdAndUser($id, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        if ($this->isPost()) {
            $this->update($vehicle);
            return;
        }

        $this->render('vehicles/edit', [
            'vehicle' => $vehicle,
            'fuelTypes' => $this->fuelTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Actualizar vehículo
     */
    private function update(array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=vehicle_edit&id=' . $vehicle['id']);
            return;
        }

        $data = [
            'type' => $this->post('type', 'motorcycle'),
            'brand' => trim($this->post('brand', '')),
            'model' => trim($this->post('model', '')),
            'year' => (int) $this->post('year', date('Y')),
            'displacement' => $this->post('displacement') ? (int) $this->post('displacement') : null,
            'license_plate' => strtoupper(trim($this->post('license_plate', ''))),
            'current_km' => (int) $this->post('current_km', 0),
            'default_fuel_type_id' => (int) $this->post('default_fuel_type_id', 1)
        ];

        $validator = new Validator($data);
        $validator
            ->required('brand')
            ->required('model')
            ->required('year')
            ->year('year')
            ->required('license_plate')
            ->minValue('current_km', 0);

        if ($validator->fails()) {
            $this->render('vehicles/edit', [
                'error' => $validator->firstError(),
                'vehicle' => array_merge($vehicle, $data),
                'fuelTypes' => $this->fuelTypes,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Procesar nueva foto si se subió
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->uploadPhoto($_FILES['photo']);
            if ($photoPath) {
                // Eliminar foto anterior si existe
                if ($vehicle['photo'] && file_exists($vehicle['photo'])) {
                    unlink($vehicle['photo']);
                }
                $data['photo'] = $photoPath;
            }
        }

        $this->vehicleModel->update($vehicle['id'], $data);
        $this->flash('success', 'Vehículo actualizado correctamente');
        $this->redirect('index.php?action=vehicle_show&id=' . $vehicle['id']);
    }

    /**
     * Eliminar vehículo
     */
    public function delete(): void
    {
        Auth::require();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $id = (int) $this->post('id');
        $vehicle = $this->vehicleModel->getByIdAndUser($id, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        // Eliminar foto si existe
        if ($vehicle['photo'] && file_exists($vehicle['photo'])) {
            unlink($vehicle['photo']);
        }

        $this->vehicleModel->delete($id);
        $this->flash('success', 'Vehículo eliminado correctamente');
        $this->redirect('index.php?action=dashboard');
    }

    /**
     * Subir foto de vehículo
     */
    private function uploadPhoto(array $file): ?string
    {
        $config = require __DIR__ . '/../config/app.php';
        $uploadDir = $config['uploads']['vehicles'];
        $maxSize = $config['uploads']['max_size'];
        $allowedTypes = $config['uploads']['allowed_types'];

        // Verificar tamaño
        if ($file['size'] > $maxSize) {
            return null;
        }

        // Verificar tipo
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            return null;
        }

        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generar nombre único
        $filename = uniqid('vehicle_') . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }

        return null;
    }
}
