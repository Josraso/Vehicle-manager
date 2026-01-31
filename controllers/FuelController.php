<?php
/**
 * Controller de Repostajes - CRUD completo
 */

class FuelController extends Controller
{
    private FuelLog $fuelModel;
    private Vehicle $vehicleModel;
    private OdometerLog $odometerModel;

    public function __construct()
    {
        $this->fuelModel = new FuelLog();
        $this->vehicleModel = new Vehicle();
        $this->odometerModel = new OdometerLog();
    }

    /**
     * Formulario crear repostaje
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

        $fuelTypes = $this->fuelModel->getFuelTypes();

        $this->render('fuel/create', [
            'vehicle' => $vehicle,
            'fuelTypes' => $fuelTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Guardar repostaje
     */
    private function store(array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=fuel_create&vehicle_id=' . $vehicle['id']);
            return;
        }

        $data = [
            'vehicle_id' => $vehicle['id'],
            'fuel_type_id' => (int) $this->post('fuel_type_id', 1),
            'date' => $this->post('date', date('Y-m-d')),
            'km' => (int) $this->post('km', 0),
            'liters' => (float) $this->post('liters', 0),
            'total_cost' => (float) $this->post('total_cost', 0),
            'full_tank' => $this->post('full_tank') ? 1 : 0,
            'notes' => trim($this->post('notes', ''))
        ];

        $validator = new Validator($data);
        $validator
            ->required('date', 'La fecha es obligatoria')
            ->date('date')
            ->required('km', 'El kilometraje es obligatorio')
            ->minValue('km', 0, 'El kilometraje no puede ser negativo')
            ->required('liters', 'Los litros son obligatorios')
            ->minValue('liters', 0.01, 'Los litros deben ser mayores a 0')
            ->required('total_cost', 'El coste total es obligatorio')
            ->minValue('total_cost', 0.01, 'El coste debe ser mayor a 0');

        if ($validator->fails()) {
            $fuelTypes = $this->fuelModel->getFuelTypes();
            $this->render('fuel/create', [
                'vehicle' => $vehicle,
                'fuelTypes' => $fuelTypes,
                'error' => $validator->firstError(),
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Validar que el km sea mayor o igual al actual
        if ($data['km'] < $vehicle['current_km']) {
            $fuelTypes = $this->fuelModel->getFuelTypes();
            $this->render('fuel/create', [
                'vehicle' => $vehicle,
                'fuelTypes' => $fuelTypes,
                'error' => 'El kilometraje no puede ser menor al actual (' . number_format($vehicle['current_km']) . ' km)',
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $fuelLogId = $this->fuelModel->createLog($data);

        if ($fuelLogId) {
            // Actualizar km del vehículo
            $this->vehicleModel->updateKm($vehicle['id'], $data['km']);

            // Registrar en historial de km
            $this->odometerModel->logFromFuel($vehicle['id'], $data['km'], $data['date'], $fuelLogId);

            $this->flash('success', 'Repostaje registrado correctamente');
            $this->redirect('index.php?action=vehicle_show&id=' . $vehicle['id']);
        } else {
            $this->flash('error', 'Error al registrar el repostaje');
            $this->redirect('index.php?action=fuel_create&vehicle_id=' . $vehicle['id']);
        }
    }

    /**
     * Formulario editar repostaje
     */
    public function edit(): void
    {
        Auth::require();

        $id = (int) $this->get('id');
        $fuelLog = $this->fuelModel->getByIdAndUser($id, Auth::id());

        if (!$fuelLog) {
            $this->flash('error', 'Repostaje no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $vehicle = $this->vehicleModel->find($fuelLog['vehicle_id']);

        if ($this->isPost()) {
            $this->update($fuelLog, $vehicle);
            return;
        }

        $fuelTypes = $this->fuelModel->getFuelTypes();

        $this->render('fuel/edit', [
            'fuelLog' => $fuelLog,
            'vehicle' => $vehicle,
            'fuelTypes' => $fuelTypes,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Actualizar repostaje
     */
    private function update(array $fuelLog, array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=fuel_edit&id=' . $fuelLog['id']);
            return;
        }

        $data = [
            'fuel_type_id' => (int) $this->post('fuel_type_id', 1),
            'date' => $this->post('date', date('Y-m-d')),
            'km' => (int) $this->post('km', 0),
            'liters' => (float) $this->post('liters', 0),
            'total_cost' => (float) $this->post('total_cost', 0),
            'full_tank' => $this->post('full_tank') ? 1 : 0,
            'notes' => trim($this->post('notes', ''))
        ];

        $validator = new Validator($data);
        $validator
            ->required('date')
            ->date('date')
            ->required('km')
            ->minValue('km', 0)
            ->required('liters')
            ->minValue('liters', 0.01)
            ->required('total_cost')
            ->minValue('total_cost', 0.01);

        if ($validator->fails()) {
            $fuelTypes = $this->fuelModel->getFuelTypes();
            $this->render('fuel/edit', [
                'fuelLog' => array_merge($fuelLog, $data),
                'vehicle' => $vehicle,
                'fuelTypes' => $fuelTypes,
                'error' => $validator->firstError(),
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $this->fuelModel->updateLog($fuelLog['id'], $data);

        // Actualizar registro de odómetro
        $this->odometerModel->deleteBySource('fuel', $fuelLog['id']);
        $this->odometerModel->logFromFuel($vehicle['id'], $data['km'], $data['date'], $fuelLog['id']);

        // Recalcular km máximo del vehículo si es necesario
        $lastOdometer = $this->odometerModel->getLastEntry($vehicle['id']);
        if ($lastOdometer) {
            $this->vehicleModel->update($vehicle['id'], ['current_km' => $lastOdometer['km']]);
        }

        $this->flash('success', 'Repostaje actualizado correctamente');
        $this->redirect('index.php?action=vehicle_show&id=' . $vehicle['id']);
    }

    /**
     * Eliminar repostaje
     */
    public function delete(): void
    {
        Auth::require();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $id = (int) $this->post('id');
        $fuelLog = $this->fuelModel->getByIdAndUser($id, Auth::id());

        if (!$fuelLog) {
            $this->flash('error', 'Repostaje no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $vehicleId = $fuelLog['vehicle_id'];

        // Eliminar registro de odómetro asociado
        $this->odometerModel->deleteBySource('fuel', $id);

        // Eliminar repostaje
        $this->fuelModel->delete($id);

        $this->flash('success', 'Repostaje eliminado correctamente');
        $this->redirect('index.php?action=vehicle_show&id=' . $vehicleId);
    }
}
