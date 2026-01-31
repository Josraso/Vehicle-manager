# Vehicle Manager

Aplicación web para el control completo de vehículos (motos y coches).

## Características

### Usuarios
- Registro y login seguro
- Recuperación de contraseña
- Perfil editable
- Modo oscuro/claro

### Vehículos
- Gestión de múltiples vehículos
- Soporte para motos y coches
- Subida de fotos
- Datos: marca, modelo, año, cilindrada, matrícula

### Repostajes
- Registro completo de repostajes
- Tipos de combustible
- Cálculo automático de precio por litro
- Historial completo

### Mantenimientos
- Tipos predefinidos con intervalos
- Recordatorios por km o fecha
- Historial de operaciones

### Estadísticas
- Gráficos mensuales/anuales
- Consumo medio (L/100km)
- Coste por km
- Distribución de gastos

### Extras
- Exportar a CSV
- Informe PDF imprimible
- Backup completo

## Instalación

### Requisitos
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite

### Pasos

1. **Clonar o copiar archivos** al directorio web:
```bash
cp -r Vehicle-manager /var/www/html/
```

2. **Crear la base de datos**:
```bash
mysql -u root -p < database.sql
```

3. **Configurar conexión** en `config/database.php`:
```php
return [
    'host' => 'localhost',
    'database' => 'vehicle_manager',
    'username' => 'tu_usuario',
    'password' => 'tu_contraseña',
    ...
];
```

4. **Permisos de escritura** para uploads:
```bash
chmod 755 uploads/vehicles
```

5. **Acceder** a `http://localhost/Vehicle-manager/`

## Usuario Demo

- Email: `demo@test.com`
- Password: `123456`

## Estructura

```
Vehicle-manager/
├── index.php           # Router principal
├── config/             # Configuración
├── core/               # Clases base (MVC)
├── models/             # Modelos de datos
├── controllers/        # Controladores
├── views/              # Vistas
│   ├── layouts/        # Layout principal
│   ├── auth/           # Login, registro
│   ├── vehicles/       # CRUD vehículos
│   ├── fuel/           # CRUD repostajes
│   ├── maintenance/    # CRUD mantenimientos
│   ├── odometer/       # Historial km
│   ├── profile/        # Perfil usuario
│   ├── stats/          # Estadísticas
│   └── export/         # Exportación
├── assets/
│   ├── css/            # Estilos
│   └── js/             # JavaScript
├── uploads/            # Fotos subidas
└── database.sql        # Esquema BD
```

## Seguridad

- Contraseñas hasheadas con bcrypt
- Prepared statements (PDO)
- Tokens CSRF
- Validación frontend + backend
- Sesiones seguras

## Tecnologías

- PHP 8+
- MySQL/MariaDB
- Bootstrap 5.3
- Chart.js
- PDO
