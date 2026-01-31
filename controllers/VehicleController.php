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

        $this->render('vehicles/index', [
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
