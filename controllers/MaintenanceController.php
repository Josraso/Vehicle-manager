<?php
/**
 * Controller de Mantenimientos - CRUD completo
 */

class MaintenanceController extends Controller
{
    private MaintenanceLog $maintenanceModel;
    private Vehicle $vehicleModel;
    private OdometerLog $odometerModel;

    public function __construct()
    {
        $this->maintenanceModel = new MaintenanceLog();
        $this->vehicleModel = new Vehicle();
        $this->odometerModel = new OdometerLog();
    }

    /**
     * Formulario crear mantenimiento
     */
    public function create(): void
    {
        Auth::require();

        $vehicleId = (int) $this->get('vehicle_id');
        $vehicle = $this->vehicleModel->getByIdAndUser($vehicleId, Auth::id());

        if (!$vehicle) {
            $this->flash('error', 'Vehículo no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        if ($this->isPost()) {
            $this->store($vehicle);
            return;
        }

        $maintenanceTypes = $this->maintenanceModel->getMaintenanceTypes();

        $this->render('maintenance/create', [
            'vehicle' => $vehicle,
            'maintenanceTypes' => $maintenanceTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Guardar mantenimiento
     */
    private function store(array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=maintenance_create&vehicle_id=' . $vehicle['id']);
            return;
        }

        // Obtener valores de next_km y next_date (editables por el usuario)
        $nextKm = $this->post('next_km');
        $nextDate = $this->post('next_date');

        $data = [
            'vehicle_id' => $vehicle['id'],
            'maintenance_type_id' => (int) $this->post('maintenance_type_id', 1),
            'date' => $this->post('date', date('Y-m-d')),
            'km' => (int) $this->post('km', 0),
            'cost' => (float) $this->post('cost', 0),
            'notes' => trim($this->post('notes', '')),
            'next_km' => !empty($nextKm) ? (int) $nextKm : null,
            'next_date' => !empty($nextDate) ? $nextDate : null
        ];

        $validator = new Validator($data);
        $validator
            ->required('date', 'La fecha es obligatoria')
            ->date('date')
            ->required('km', 'El kilometraje es obligatorio')
            ->minValue('km', 0, 'El kilometraje no puede ser negativo')
            ->minValue('cost', 0, 'El coste no puede ser negativo');

        if ($validator->fails()) {
            $maintenanceTypes = $this->maintenanceModel->getMaintenanceTypes();
            $this->render('maintenance/create', [
                'vehicle' => $vehicle,
                'maintenanceTypes' => $maintenanceTypes,
                'error' => $validator->firstError(),
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $maintLogId = $this->maintenanceModel->createLog($data);

        if ($maintLogId) {
            // Actualizar km del vehículo si es mayor
            if ($data['km'] > $vehicle['current_km']) {
                $this->vehicleModel->updateKm($vehicle['id'], $data['km']);
            }

            // Registrar en historial de km
            $this->odometerModel->logFromMaintenance($vehicle['id'], $data['km'], $data['date'], $maintLogId);

            $this->flash('success', 'Mantenimiento registrado correctamente');
            $this->redirect('index.php?action=vehicle_show&id=' . $vehicle['id']);
        } else {
            $this->flash('error', 'Error al registrar el mantenimiento');
            $this->redirect('index.php?action=maintenance_create&vehicle_id=' . $vehicle['id']);
        }
    }

    /**
     * Formulario editar mantenimiento
     */
    public function edit(): void
    {
        Auth::require();

        $id = (int) $this->get('id');
        $maintLog = $this->maintenanceModel->getByIdAndUser($id, Auth::id());

        if (!$maintLog) {
            $this->flash('error', 'Mantenimiento no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $vehicle = $this->vehicleModel->find($maintLog['vehicle_id']);

        if ($this->isPost()) {
            $this->update($maintLog, $vehicle);
            return;
        }

        $maintenanceTypes = $this->maintenanceModel->getMaintenanceTypes();

        $this->render('maintenance/edit', [
            'maintLog' => $maintLog,
            'vehicle' => $vehicle,
            'maintenanceTypes' => $maintenanceTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Actualizar mantenimiento
     */
    private function update(array $maintLog, array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=maintenance_edit&id=' . $maintLog['id']);
            return;
        }

        // Obtener valores de next_km y next_date (editables por el usuario)
        $nextKm = $this->post('next_km');
        $nextDate = $this->post('next_date');

        $data = [
            'maintenance_type_id' => (int) $this->post('maintenance_type_id', 1),
            'date' => $this->post('date', date('Y-m-d')),
            'km' => (int) $this->post('km', 0),
            'cost' => (float) $this->post('cost', 0),
            'notes' => trim($this->post('notes', '')),
            'next_km' => !empty($nextKm) ? (int) $nextKm : null,
            'next_date' => !empty($nextDate) ? $nextDate : null
        ];

        $validator = new Validator($data);
        $validator
            ->required('date')
            ->date('date')
            ->required('km')
            ->minValue('km', 0)
            ->minValue('cost', 0);

        if ($validator->fails()) {
            $maintenanceTypes = $this->maintenanceModel->getMaintenanceTypes();
            $this->render('maintenance/edit', [
                'maintLog' => array_merge($maintLog, $data),
                'vehicle' => $vehicle,
                'maintenanceTypes' => $maintenanceTypes,
                'error' => $validator->firstError(),
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $this->maintenanceModel->updateLog($maintLog['id'], $data);

        // Actualizar registro de odómetro
        $this->odometerModel->deleteBySource('maintenance', $maintLog['id']);
        $this->odometerModel->logFromMaintenance($vehicle['id'], $data['km'], $data['date'], $maintLog['id']);

        // Recalcular km máximo del vehículo si es necesario
        $lastOdometer = $this->odometerModel->getLastEntry($vehicle['id']);
        if ($lastOdometer) {
            $this->vehicleModel->update($vehicle['id'], ['current_km' => $lastOdometer['km']]);
        }

        $this->flash('success', 'Mantenimiento actualizado correctamente');
        $this->redirect('index.php?action=vehicle_show&id=' . $vehicle['id']);
    }

    /**
     * Eliminar mantenimiento
     */
    public function delete(): void
    {
        Auth::require();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $id = (int) $this->post('id');
        $maintLog = $this->maintenanceModel->getByIdAndUser($id, Auth::id());

        if (!$maintLog) {
            $this->flash('error', 'Mantenimiento no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $vehicleId = $maintLog['vehicle_id'];

        // Eliminar registro de odómetro asociado
        $this->odometerModel->deleteBySource('maintenance', $id);

        // Eliminar mantenimiento
        $this->maintenanceModel->delete($id);

        $this->flash('success', 'Mantenimiento eliminado correctamente');
        $this->redirect('index.php?action=vehicle_show&id=' . $vehicleId);
    }
}
