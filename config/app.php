<?php
/**
 * Configuración General de la Aplicación
 */

return [
    'name' => 'Vehicle Manager',
    'url' => 'http://localhost/Vehicle-manager',
    'debug' => true,

    // Configuración de uploads
    'uploads' => [
        'vehicles' => 'uploads/vehicles/',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ],

    // Configuración de sesión
    'session' => [
        'lifetime' => 7200, // 2 horas
        'name' => 'vehicle_manager_session'
    ],

    // Email (para recuperar contraseña)
    'mail' => [
        'from' => 'noreply@vehiclemanager.com',
        'from_name' => 'Vehicle Manager'
    ]
];
