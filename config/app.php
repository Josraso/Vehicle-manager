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
    ],

    // Cron - Token para el endpoint de procesamiento de cola de emails
    // Uso: curl "http://tudominio.com/index.php?action=cron_queue&token=ESTE_TOKEN"
    // Crontab: * * * * * curl -s "http://tudominio.com/index.php?action=cron_queue&token=4d3de086c2ffbdebee1341cd9504f31c280ac9c26f501044911742444698fcbc" > /dev/null 2>&1
    'cron' => [
        'token' => '4d3de086c2ffbdebee1341cd9504f31c280ac9c26f501044911742444698fcbc'
    ]
];
