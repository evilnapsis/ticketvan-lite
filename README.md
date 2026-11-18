# TicketVan Lite

Sistema venta de boletos de autobuses, construido con la arquitectura **Legobox 5**.

## Tecnologías

- **Backend:** PHP 8.2+ · PDO (`utf8mb4`) · FastRoute
- **Vistas:** Twig 3 · CoreUI v5 / Bootstrap 5 · Bootstrap Icons · SweetAlert2 · DataTables · Select2 · Chart.js
- **Impresión:** FPDF (boletos térmicos de 80 mm en tira continua)
- **Base de datos:** MySQL 8.0+ / MariaDB (`InnoDB`, `utf8mb4_unicode_ci`)

## Estructura del Proyecto

```text
ticketvan-lite/
├── assets/                   # Assets estáticos (CoreUI, Bootstrap Icons, JS, CSS, vendors)
├── core/
│   ├── autoload.php          # Autocarga del núcleo y FPDF
│   ├── fpdf/                 # Librería FPDF para tickets térmicos
│   ├── controller/           # Clases base (Database, Req, Response, Session, ViewEngine, etc.)
│   └── app/
│       ├── autoload.php      # Autoloader de modelos, controladores y servicios
│       ├── routes.php        # Rutas HTTP con FastRoute
│       ├── controller/       # Controladores MVC
│       ├── model/            # Modelos de datos (*Data.php)
│       └── service/          # AuthService y TicketService
├── public/                   # Vistas Twig (.html.twig)
├── vendor/                   # Dependencias Composer (Twig, FastRoute)
├── .htaccess                 # Reescritura Apache hacia index.php
├── index.php                 # Front Controller (FastRoute + CSRF + RBAC)
├── schema.sql                # Estructura de base de datos (7 tablas) y datos semilla
└── demo_data.sql             # Datos de demostración (últimos 30 días)
```

## Base de Datos (`ticketvan_lite`)

El esquema se reduce a **7 tablas** esenciales:

1. `setting` — Configuración general de la empresa y titulo del boleto.
2. `branch` — Terminales / sucursales de origen, destino y taquillas.
3. `user` — Usuarios con rol `admin` o `seller`.
4. `vehicle` — Flota de unidades (`van`, `bus`) y total de asientos.
5. `passenger` — Padrón opcional de pasajeros frecuentes.
6. `trip` — Corridas con origen, destino, vehículo, fecha/hora, precio único y estado.
7. `ticket` — Boletos emitidos con control anti-sobreventa mediante la columna generada `active_seat` e índice único `(trip_id, active_seat)`.

## Instalación

1. Importar la estructura y datos iniciales en MySQL:
   ```bash
   mysql -u root --default-character-set=utf8mb4 < schema.sql
   ```
2. *(Opcional)* Importar datos de demostración de los últimos 30 días:
   ```bash
   mysql -u root --default-character-set=utf8mb4 < demo_data.sql
   ```
3. Configurar las credenciales de base de datos en `core/controller/Database.php` si difieren de `root` sin contraseña en `localhost`.
4. Asegurar que `mod_rewrite` de Apache esté habilitado para el funcionamiento de `.htaccess`.

## Acceso Inicial

- **URL:** `http://localhost/update_mv_2026/ticketvan-lite/`
- **Usuario Administrador:** `admin` / `admin`
- **Taquilleros (demo):** `ana` / `admin` y `carlos` / `admin`

## Módulos Principales

- **Dashboard (`/home`):** Resumen de ingresos y boletos del día, gráfica de los últimos 30 días y corridas de hoy.
- **Venta de Boletos (`/pos`):** Mapa interactivo de asientos, selección múltiple, autocompletado de pasajero, método de pago y emisión en PDF térmico de 80 mm (`/tickets/print?ids=...`).
- **Boletos Emitidos (`/tickets`):** Consulta con filtros, detalle, reimpresión PDF (`/tickets/{id}/print`) y cancelación con liberación automática de asiento.
- **Corridas (`/trips`):** Programación directa de origen, destino, vehículo, fecha/hora y precio único, además de lista imprimible de pasajeros (`/trips/{id}/manifest`).
- **Catálogos:** Vehículos (`/vehicles`), Terminales (`/branches`) y Pasajeros (`/passengers`).
- **Administración (solo `admin`):** Reporte de Ventas (`/reports`), Reporte por Corrida (`/reportbytrip`), Usuarios (`/users`) y Configuración (`/settings`).
