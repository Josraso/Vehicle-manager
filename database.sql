-- =====================================================
-- VEHICLE MANAGER - BASE DE DATOS COMPLETA v2.0
-- Con Panel de Administración y Sistema de Emails
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS vehicle_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_manager;

-- =====================================================
-- TABLA: users (con roles)
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `is_active` TINYINT(1) DEFAULT 1,
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_expires` DATETIME DEFAULT NULL,
    `theme` ENUM('light', 'dark') DEFAULT 'light',
    `email_notifications` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email` (`email`),
    KEY `idx_reset_token` (`reset_token`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: settings (configuración global)
-- =====================================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración inicial
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
-- General
('site_name', 'Vehicle Manager', 'string', 'Nombre del sitio'),
('site_description', 'Control completo de tus vehículos', 'string', 'Descripción del sitio'),
('admin_email', 'admin@example.com', 'string', 'Email del administrador'),
-- Email
('mail_driver', 'smtp', 'string', 'Driver de correo: smtp, sendmail, mail'),
('mail_host', 'smtp.gmail.com', 'string', 'Servidor SMTP'),
('mail_port', '587', 'integer', 'Puerto SMTP'),
('mail_username', '', 'string', 'Usuario SMTP'),
('mail_password', '', 'string', 'Contraseña SMTP'),
('mail_encryption', 'tls', 'string', 'Encriptación: tls, ssl, none'),
('mail_from_address', 'noreply@example.com', 'string', 'Email remitente'),
('mail_from_name', 'Vehicle Manager', 'string', 'Nombre remitente'),
-- Recordatorios
('reminder_days_before', '7', 'integer', 'Días de antelación para recordatorios'),
('reminder_enabled', '1', 'boolean', 'Activar recordatorios por email'),
-- Registro
('allow_registration', '1', 'boolean', 'Permitir registro de usuarios');

-- =====================================================
-- TABLA: email_templates (plantillas de email)
-- =====================================================
DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE `email_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `variables` TEXT DEFAULT NULL COMMENT 'Variables disponibles en JSON',
    `is_active` TINYINT(1) DEFAULT 1,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas de email predefinidas
INSERT INTO `email_templates` (`slug`, `name`, `subject`, `body`, `variables`) VALUES
('welcome', 'Bienvenida', 'Bienvenido a {site_name}',
'<h2>¡Bienvenido {user_name}!</h2>
<p>Gracias por registrarte en <strong>{site_name}</strong>.</p>
<p>Ya puedes empezar a gestionar tus vehículos, registrar repostajes y mantenimientos.</p>
<p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
<p>Saludos,<br>El equipo de {site_name}</p>',
'["user_name", "user_email", "site_name", "site_url"]'),

('password_reset', 'Recuperar Contraseña', 'Restablecer contraseña - {site_name}',
'<h2>Hola {user_name}</h2>
<p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
<p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
<p><a href="{reset_link}" style="background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Restablecer Contraseña</a></p>
<p>Este enlace expirará en 1 hora.</p>
<p>Si no solicitaste este cambio, ignora este mensaje.</p>
<p>Saludos,<br>El equipo de {site_name}</p>',
'["user_name", "user_email", "reset_link", "site_name"]'),

('maintenance_reminder', 'Recordatorio de Mantenimiento', 'Recordatorio: {maintenance_type} - {vehicle_name}',
'<h2>Recordatorio de Mantenimiento</h2>
<p>Hola {user_name},</p>
<p>Te recordamos que se acerca el mantenimiento de tu vehículo:</p>
<table style="border-collapse:collapse;width:100%;max-width:500px;">
<tr style="background:#f8f9fa;"><td style="padding:10px;border:1px solid #ddd;"><strong>Vehículo:</strong></td><td style="padding:10px;border:1px solid #ddd;">{vehicle_name}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;"><strong>Mantenimiento:</strong></td><td style="padding:10px;border:1px solid #ddd;">{maintenance_type}</td></tr>
<tr style="background:#f8f9fa;"><td style="padding:10px;border:1px solid #ddd;"><strong>Fecha límite:</strong></td><td style="padding:10px;border:1px solid #ddd;">{due_date}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;"><strong>Km límite:</strong></td><td style="padding:10px;border:1px solid #ddd;">{due_km}</td></tr>
</table>
<p style="margin-top:20px;">No olvides realizar el mantenimiento a tiempo para mantener tu vehículo en óptimas condiciones.</p>
<p>Saludos,<br>El equipo de {site_name}</p>',
'["user_name", "vehicle_name", "maintenance_type", "due_date", "due_km", "site_name"]'),

('test_email', 'Email de Prueba', 'Prueba de configuración - {site_name}',
'<h2>¡Configuración correcta!</h2>
<p>Este es un email de prueba enviado desde <strong>{site_name}</strong>.</p>
<p>Si estás leyendo este mensaje, la configuración de correo funciona correctamente.</p>
<p><strong>Detalles técnicos:</strong></p>
<ul>
<li>Driver: {mail_driver}</li>
<li>Host: {mail_host}</li>
<li>Puerto: {mail_port}</li>
<li>Fecha: {date}</li>
</ul>
<p>Saludos,<br>El equipo de {site_name}</p>',
'["site_name", "mail_driver", "mail_host", "mail_port", "date"]');

-- =====================================================
-- TABLA: activity_logs (registro de actividad)
-- =====================================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `description` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: email_queue (cola de emails pendientes)
-- =====================================================
DROP TABLE IF EXISTS `email_queue`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sent_reminders (recordatorios enviados)
-- =====================================================
DROP TABLE IF EXISTS `sent_reminders`;
CREATE TABLE `sent_reminders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `maintenance_log_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `reminder_type` ENUM('email', 'push') DEFAULT 'email',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_maintenance_user_type` (`maintenance_log_id`, `user_id`, `reminder_type`),
    KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: fuel_types (tipos de combustible)
-- =====================================================
DROP TABLE IF EXISTS `fuel_types`;
CREATE TABLE `fuel_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `fuel_types` (`name`) VALUES
('Gasolina 95'),
('Gasolina 98'),
('Diesel'),
('Diesel+'),
('Eléctrico'),
('Híbrido');

-- =====================================================
-- TABLA: maintenance_types (tipos de mantenimiento)
-- =====================================================
DROP TABLE IF EXISTS `maintenance_types`;
CREATE TABLE `maintenance_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `default_km_interval` INT DEFAULT NULL,
    `default_days_interval` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `maintenance_types` (`name`, `default_km_interval`, `default_days_interval`) VALUES
('Cambio de aceite', 5000, 180),
('Filtro de aceite', 10000, 365),
('Filtro de aire', 15000, 365),
('Bujías', 20000, NULL),
('Cadena', 20000, NULL),
('Neumático delantero', 15000, NULL),
('Neumático trasero', 12000, NULL),
('Pastillas freno delantero', 15000, NULL),
('Pastillas freno trasero', 20000, NULL),
('Líquido de frenos', 40000, 730),
('Refrigerante', 40000, 730),
('Batería', NULL, 730),
('Revisión ITV', NULL, 730),
('Seguro', NULL, 365),
('Revisión general', 10000, 365),
('Otro', NULL, NULL);

-- =====================================================
-- TABLA: vehicles
-- =====================================================
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('motorcycle', 'car') DEFAULT 'motorcycle',
    `brand` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year` YEAR NOT NULL,
    `displacement` INT DEFAULT NULL COMMENT 'Cilindrada en cc',
    `license_plate` VARCHAR(20) NOT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `current_km` INT UNSIGNED DEFAULT 0,
    `default_fuel_type_id` INT UNSIGNED DEFAULT 1 COMMENT 'Tipo de combustible predeterminado',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_license_plate` (`license_plate`),
    KEY `idx_fuel_type` (`default_fuel_type_id`),
    CONSTRAINT `fk_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vehicles_fuel_type` FOREIGN KEY (`default_fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: odometer_logs (historial de kilometraje)
-- =====================================================
DROP TABLE IF EXISTS `odometer_logs`;
CREATE TABLE `odometer_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT UNSIGNED NOT NULL,
    `km` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `source` ENUM('manual', 'fuel', 'maintenance') DEFAULT 'manual',
    `source_id` INT UNSIGNED DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_id` (`vehicle_id`),
    KEY `idx_date` (`date`),
    CONSTRAINT `fk_odometer_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: fuel_logs (repostajes)
-- =====================================================
DROP TABLE IF EXISTS `fuel_logs`;
CREATE TABLE `fuel_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT UNSIGNED NOT NULL,
    `fuel_type_id` INT UNSIGNED DEFAULT 1,
    `date` DATE NOT NULL,
    `km` INT UNSIGNED NOT NULL,
    `liters` DECIMAL(10,2) NOT NULL,
    `price_per_liter` DECIMAL(10,3) DEFAULT NULL,
    `total_cost` DECIMAL(10,2) NOT NULL,
    `full_tank` TINYINT(1) DEFAULT 1 COMMENT '1=depósito lleno, 0=parcial',
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_id` (`vehicle_id`),
    KEY `idx_date` (`date`),
    KEY `idx_fuel_type` (`fuel_type_id`),
    CONSTRAINT `fk_fuel_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: maintenance_logs (mantenimientos)
-- =====================================================
DROP TABLE IF EXISTS `maintenance_logs`;
CREATE TABLE `maintenance_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT UNSIGNED NOT NULL,
    `maintenance_type_id` INT UNSIGNED DEFAULT NULL,
    `date` DATE NOT NULL,
    `km` INT UNSIGNED NOT NULL,
    `cost` DECIMAL(10,2) DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `next_km` INT UNSIGNED DEFAULT NULL COMMENT 'Próximo km para recordatorio (editable)',
    `next_date` DATE DEFAULT NULL COMMENT 'Próxima fecha para recordatorio (editable)',
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_id` (`vehicle_id`),
    KEY `idx_date` (`date`),
    KEY `idx_type` (`maintenance_type_id`),
    KEY `idx_next_km` (`next_km`),
    KEY `idx_next_date` (`next_date`),
    KEY `idx_reminder` (`reminder_sent`),
    CONSTRAINT `fk_maintenance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_maintenance_type` FOREIGN KEY (`maintenance_type_id`) REFERENCES `maintenance_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USUARIO ADMINISTRADOR
-- Email: admin@admin.com | Password: admin123
-- =====================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrador', 'admin@admin.com', '$2y$12$9CgfiBWIddUQeMWyqO9Pu.wpDzD0zYkTN0GJV8DjdhEviAewgwdaW', 'admin');

-- =====================================================
-- USUARIO DE PRUEBA
-- Email: demo@test.com | Password: 123456
-- =====================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Usuario Demo', 'demo@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
