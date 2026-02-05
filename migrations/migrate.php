<?php
/**
 * Vehicle Manager - Script de migración de base de datos
 *
 * Actualiza el esquema de una instalación existente sin perder datos.
 * Es idempotente: se puede ejecutar varias veces sin problema.
 *
 * Uso desde línea de comandos:
 *     php migrations/migrate.php
 *
 * O desde el navegador (una vez ejecutado, eliminar el archivo):
 *     http://tudominio/migrations/migrate.php
 */

$config = require __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "Vehicle Manager - Migración de base de datos\n";
echo str_repeat('=', 50) . "\n\n";

$applied = 0;

// ============================================================
// Migración 1: sent_reminders → añadir columna reminder_type
// ============================================================
echo "Tabla sent_reminders:\n";

$columns = array_column(
    $pdo->query("SHOW COLUMNS FROM sent_reminders")->fetchAll(),
    'Field'
);

if (!in_array('reminder_type', $columns)) {
    $pdo->exec("ALTER TABLE `sent_reminders` ADD COLUMN `reminder_type` ENUM('email', 'push') DEFAULT 'email' AFTER `user_id`");
    echo "  [+] Añadida columna reminder_type\n";
    $applied++;
} else {
    echo "  [ ] Columna reminder_type ya existe\n";
}

// ============================================================
// Migración 2: sent_reminders → actualizar índice único
// El índice correcto debe ser (maintenance_log_id, user_id, reminder_type)
// ============================================================
$indexes = $pdo->query("SHOW INDEX FROM sent_reminders")->fetchAll();

// Eliminar cualquier índice único sobre maintenance_log_id que no sea el definitivo
$eliminados = [];
foreach ($indexes as $idx) {
    if ($idx['Non_unique'] == 0
        && $idx['Key_name'] !== 'PRIMARY'
        && $idx['Column_name'] === 'maintenance_log_id'
        && $idx['Key_name'] !== 'idx_maintenance_user_type'
        && !in_array($idx['Key_name'], $eliminados)
    ) {
        $pdo->exec("ALTER TABLE `sent_reminders` DROP INDEX `{$idx['Key_name']}`");
        echo "  [+] Eliminado índice antiguo: {$idx['Key_name']}\n";
        $eliminados[] = $idx['Key_name'];
        $applied++;
    }
}

// Añadir índice correcto si no existe
$hasCorrectIndex = false;
foreach ($indexes as $idx) {
    if ($idx['Key_name'] === 'idx_maintenance_user_type') {
        $hasCorrectIndex = true;
        break;
    }
}

if (!$hasCorrectIndex) {
    $pdo->exec("ALTER TABLE `sent_reminders` ADD UNIQUE KEY `idx_maintenance_user_type` (`maintenance_log_id`, `user_id`, `reminder_type`)");
    echo "  [+] Añadido índice único idx_maintenance_user_type\n";
    $applied++;
} else {
    echo "  [ ] Índice idx_maintenance_user_type ya existe\n";
}

echo "\n";

// ============================================================
// Migración 3: crear tabla email_queue si no existe
// ============================================================
echo "Tabla email_queue:\n";

$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM email_queue LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    // tabla no existe
}

if (!$tableExists) {
    $pdo->exec("
        CREATE TABLE `email_queue` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `to_email` VARCHAR(255) NOT NULL,
            `to_name` VARCHAR(100) DEFAULT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body` TEXT NOT NULL,
            `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            `attempts` TINYINT UNSIGNED DEFAULT 0,
            `error_message` TEXT DEFAULT NULL,
            `scheduled_at` DATETIME DEFAULT NULL,
            `sent_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`),
            KEY `idx_scheduled` (`scheduled_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  [+] Tabla creada\n";
    $applied++;
} else {
    echo "  [ ] Tabla ya existe\n";
}

echo "\n" . str_repeat('=', 50) . "\n";

if ($applied > 0) {
    echo "$applied migración(es) aplicada(s) correctamente.\n";
} else {
    echo "Nada que migrar. El esquema está actualizado.\n";
}

echo "\nSi ejecutaste desde el navegador, elimina migrations/migrate.php.\n";
