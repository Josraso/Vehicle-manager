<?php
/**
 * Controller de Kilometraje - Registro manual de km
 */

class OdometerController extends Controller
{
    private OdometerLog $odometerModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->odometerModel = new OdometerLog();
        $this->vehicleModel = new Vehicle();
    }

    /**
     * Ver historial de kilometraje
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

        $logs = $this->odometerModel->getByVehicle($vehicleId, 100);
        $evolution = $this->odometerModel->getMonthlyEvolution($vehicleId);

        $this->render('odometer/index', [
            'vehicle' => $vehicle,
            'logs' => $logs,
            'evolution' => $evolution,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Formulario crear registro de km
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

        $this->render('odometer/create', [
            'vehicle' => $vehicle,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Guardar registro de km
     */
    private function store(array $vehicle): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=odometer_create&vehicle_id=' . $vehicle['id']);
            return;
        }

        $data = [
            'vehicle_id' => $vehicle['id'],
            'km' => (int) $this->post('km', 0),
            'date' => $this->post('date', date('Y-m-d')),
            'notes' => trim($this->post('notes', ''))
        ];

        $validator = new Validator($data);
        $validator
            ->required('date', 'La fecha es obligatoria')
            ->date('date')
            ->required('km', 'El kilometraje es obligatorio')
            ->minValue('km', 0, 'El kilometraje no puede ser negativo');

        if ($validator->fails()) {
            $this->render('odometer/create', [
                'vehicle' => $vehicle,
                'error' => $validator->firstError(),
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        // Validar que el km sea mayor o igual al actual
        if ($data['km'] < $vehicle['current_km']) {
            $this->render('odometer/create', [
                'vehicle' => $vehicle,
                'error' => 'El kilometraje no puede ser menor al actual (' . number_format($vehicle['current_km']) . ' km)',
                'data' => $data,
                'csrf_token' => $this->generateCsrf()
            ]);
            return;
        }

        $logId = $this->odometerModel->createLog($data);

        if ($logId) {
            // Actualizar km del vehículo
            $this->vehicleModel->updateKm($vehicle['id'], $data['km']);

            $this->flash('success', 'Kilometraje registrado correctamente');
            $this->redirect('index.php?action=odometer_index&vehicle_id=' . $vehicle['id']);
        } else {
            $this->flash('error', 'Error al registrar el kilometraje');
            $this->redirect('index.php?action=odometer_create&vehicle_id=' . $vehicle['id']);
        }
    }

    /**
     * Eliminar registro de km (solo manuales)
     */
    public function delete(): void
    {
        Auth::require();

        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $id = (int) $this->post('id');
        $log = $this->odometerModel->getByIdAndUser($id, Auth::id());

        if (!$log) {
            $this->flash('error', 'Registro no encontrado');
            $this->redirect('index.php?action=dashboard');
            return;
        }

        // Solo permitir eliminar registros manuales
        if ($log['source'] !== 'manual') {
            $this->flash('error', 'Solo se pueden eliminar registros manuales');
            $this->redirect('index.php?action=odometer_index&vehicle_id=' . $log['vehicle_id']);
            return;
        }

        $vehicleId = $log['vehicle_id'];
        $this->odometerModel->delete($id);

        $this->flash('success', 'Registro eliminado correctamente');
        $this->redirect('index.php?action=odometer_index&vehicle_id=' . $vehicleId);
    }
}
