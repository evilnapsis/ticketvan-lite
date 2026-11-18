-- =============================================================================
-- PROYECTO: TicketVan Lite (2026)
-- DESCRIPCIÓN: Sistema Ágil de Venta de Boletos y Control de Salidas para Vans y Autobuses
-- MOTOR: MySQL 8.0+ / MariaDB (InnoDB, utf8mb4_unicode_ci)
-- AUTOR: Evilnapsis / Legobox 5
-- =============================================================================

CREATE DATABASE IF NOT EXISTS ticketvan_lite
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE ticketvan_lite;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ticket;
DROP TABLE IF EXISTS trip;
DROP TABLE IF EXISTS passenger;
DROP TABLE IF EXISTS vehicle;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS branch;
DROP TABLE IF EXISTS setting;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- TABLA: setting
-- PROPÓSITO: Almacena parámetros globales de configuración de la empresa de transporte
--            (nombre, RFC, teléfono, dirección, logo y leyendas impresas en el boleto).
-- RELACIONES: 
--   - Tabla independiente de configuración clave/valor.
-- -----------------------------------------------------------------------------
CREATE TABLE setting (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`       VARCHAR(50)  NOT NULL,
  `value`     TEXT         NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_setting_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: branch
-- PROPÓSITO: Catálogo de terminales, bases y puntos de venta. Cada terminal
--            sirve como origen o destino de corridas, y como taquilla física.
-- RELACIONES:
--   - user.branch_id              -> Usuarios taquilleros asignados a la terminal [0..1:N]
--   - trip.origin_branch_id       -> Terminal de salida de la corrida [1:N]
--   - trip.destination_branch_id  -> Terminal de llegada de la corrida [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE branch (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code        VARCHAR(20)  NULL,
  name        VARCHAR(100) NOT NULL,
  city        VARCHAR(100) NOT NULL,
  address     VARCHAR(255) NULL,
  phone       VARCHAR(30)  NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branch_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: user
-- PROPÓSITO: Cuentas de acceso de personal administrativo y taquilleros.
-- RELACIONES:
--   - branch_id                   -> Terminal de trabajo asignada (branch.id) [0..1:N]
--   - ticket.user_id              -> Boletos emitidos por el usuario [1:N]
-- 
-- VALORES FIJOS:
--   * role: Perfil de acceso y privilegios en el sistema:
--       - 'admin': Administrador general con acceso total a finanzas, configuración, usuarios y catálogos.
--       - 'seller': Vendedor de taquilla; limitado a emitir boletos, ver salidas y corte propio.
--       DEFAULT: 'seller'
-- -----------------------------------------------------------------------------
CREATE TABLE `user` (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id      INT UNSIGNED NULL,
  role           ENUM('admin', 'seller') NOT NULL DEFAULT 'seller',
  name           VARCHAR(120) NOT NULL,
  username       VARCHAR(60)  NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_email (email),
  UNIQUE KEY uq_user_username (username),
  KEY idx_user_branch (branch_id),
  CONSTRAINT fk_user_branch FOREIGN KEY (branch_id) REFERENCES branch (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: vehicle
-- PROPÓSITO: Registro de la flota operativa de vans, combis, minibuses o autobuses,
--            especificando su número económico, placa y capacidad total de asientos.
-- RELACIONES:
--   - trip.vehicle_id             -> Unidades asignadas a las corridas/viajes [1:N]
-- 
-- VALORES FIJOS:
--   * type: Tipo de unidad vehicular:
--       - 'van': Unidad de transporte ligero/intermedio (10 a 19 asientos).
--       - 'bus': Autobús de mediana o larga distancia (20 a 55 asientos).
--       DEFAULT: 'van'
-- -----------------------------------------------------------------------------
CREATE TABLE vehicle (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plate        VARCHAR(20)  NOT NULL,
  name         VARCHAR(80)  NULL,
  type         ENUM('van', 'bus') NOT NULL DEFAULT 'van',
  total_seat   SMALLINT UNSIGNED NOT NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehicle_plate (plate),
  CONSTRAINT chk_vehicle_total_seat CHECK (total_seat > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: passenger
-- PROPÓSITO: Padrón de pasajeros frecuentes y clientes habituales para autocompletado
--            ágil en taquilla y contacto de emergencia.
-- RELACIONES:
--   - ticket.passenger_id         -> Boletos asociados al pasajero registrado [0..1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE passenger (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name       VARCHAR(80)  NOT NULL,
  last_name        VARCHAR(80)  NULL,
  document_number  VARCHAR(40)  NULL,
  phone            VARCHAR(30)  NULL,
  email            VARCHAR(150) NULL,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_passenger_document (document_number),
  KEY idx_passenger_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: trip
-- PROPÓSITO: Corridas o salidas específicas programadas en una fecha y hora,
--            definiendo directamente origen, destino, vehículo asignado y precio único.
-- RELACIONES:
--   - origin_branch_id            -> Terminal de salida (branch.id) [1:N]
--   - destination_branch_id       -> Terminal de llegada (branch.id) [1:N]
--   - vehicle_id                  -> Vehículo que realiza el viaje (vehicle.id) [1:N]
--   - ticket.trip_id              -> Boletos vendidos para esta salida [1:N]
-- 
-- VALORES FIJOS:
--   * status: Estado operativo de la salida:
--       - 'scheduled': Programada; venta de asientos abierta en taquilla.
--       - 'boarding': Abordaje; unidad en andén recibiendo pasajeros.
--       - 'departed': En camino; salida realizada, venta de boletos cerrada.
--       - 'completed': Finalizada con éxito en destino.
--       - 'cancelled': Cancelada por avería o contingencia meteorológica/operativa.
--       DEFAULT: 'scheduled'
-- -----------------------------------------------------------------------------
CREATE TABLE trip (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  origin_branch_id       INT UNSIGNED NOT NULL,
  destination_branch_id  INT UNSIGNED NOT NULL,
  vehicle_id             INT UNSIGNED NOT NULL,
  departure_at           DATETIME     NOT NULL,
  price                  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status                 ENUM('scheduled', 'boarding', 'departed', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  notes                  VARCHAR(255) NULL,
  created_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_trip_departure (departure_at),
  KEY idx_trip_origin (origin_branch_id),
  KEY idx_trip_destination (destination_branch_id),
  KEY idx_trip_vehicle (vehicle_id),
  CONSTRAINT fk_trip_origin      FOREIGN KEY (origin_branch_id)      REFERENCES branch (id),
  CONSTRAINT fk_trip_destination FOREIGN KEY (destination_branch_id) REFERENCES branch (id),
  CONSTRAINT fk_trip_vehicle     FOREIGN KEY (vehicle_id)            REFERENCES vehicle (id),
  CONSTRAINT chk_trip_different  CHECK (origin_branch_id <> destination_branch_id),
  CONSTRAINT chk_trip_price      CHECK (price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: ticket
-- PROPÓSITO: Emisión y cobro individual de boletos de viaje con asignación de asiento,
--            datos del pasajero y mecanismo estricto contra sobreventa en concurrencia.
-- RELACIONES:
--   - trip_id                     -> Salida o corrida correspondiente (trip.id) [1:N]
--   - passenger_id                -> Pasajero frecuente registrado o NULL si es general [0..1:N]
--   - user_id                     -> Taquillero/Cajero que emitió el boleto (user.id) [1:N]
-- 
-- VALORES FIJOS:
--   * payment_method: Método de liquidación en ventanilla:
--       - 'cash': Efectivo en taquilla.
--       - 'card': Terminal bancaria / tarjeta de débito o crédito.
--       - 'transfer': Transferencia bancaria directa / SPEI.
--       DEFAULT: 'cash'
--   * status: Estado del boleto:
--       - 'sold': Boleto vigente vendido; asiento ocupado.
--       - 'cancelled': Boleto anulado/reembolsado; asiento liberado para venta.
--       DEFAULT: 'sold'
-- 
-- REGLA ANTI-SOBREVENTA:
--   La columna generada virtual `active_seat` toma el valor de `seat_number` si el boleto
--   está 'sold', y pasa a NULL si se cancela. La restricción única `(trip_id, active_seat)`
--   garantiza que NUNCA se pueda vender el mismo asiento dos veces en el mismo viaje,
--   pero al cancelarse se libera automáticamente en el mapa del POS.
-- -----------------------------------------------------------------------------
CREATE TABLE ticket (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  trip_id          INT UNSIGNED NOT NULL,
  seat_number      SMALLINT UNSIGNED NOT NULL,
  passenger_id     INT UNSIGNED NULL,
  passenger_name   VARCHAR(160) NOT NULL,
  passenger_phone  VARCHAR(30)  NULL,
  price            DECIMAL(10,2) NOT NULL,
  payment_method   ENUM('cash', 'card', 'transfer') NOT NULL DEFAULT 'cash',
  status           ENUM('sold', 'cancelled') NOT NULL DEFAULT 'sold',
  user_id          INT UNSIGNED NOT NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cancelled_at     DATETIME     NULL,
  active_seat      SMALLINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'cancelled', NULL, seat_number)) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ticket_trip_seat (trip_id, active_seat),
  KEY idx_ticket_trip (trip_id),
  KEY idx_ticket_user (user_id),
  KEY idx_ticket_passenger (passenger_id),
  KEY idx_ticket_created (created_at),
  CONSTRAINT fk_ticket_trip      FOREIGN KEY (trip_id)      REFERENCES trip (id),
  CONSTRAINT fk_ticket_user      FOREIGN KEY (user_id)      REFERENCES `user` (id),
  CONSTRAINT fk_ticket_passenger FOREIGN KEY (passenger_id) REFERENCES passenger (id) ON DELETE SET NULL,
  CONSTRAINT chk_ticket_price CHECK (price >= 0),
  CONSTRAINT chk_ticket_seat  CHECK (seat_number > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATOS SEMILLA INICIALES (SEED DATA MÍNIMO OBLIGATORIO)
-- =============================================================================

-- 1. Parámetros de Configuración del Negocio
INSERT INTO setting (`key`, `value`) VALUES
  ('company_name',    'TicketVan Lite - Línea Express'),
  ('company_rfc',     'TVL-260101-L90'),
  ('company_phone',   '55-8000-8260 / 800-842-5382'),
  ('company_address', 'Av. Central 500, Terminal Express, CDMX'),
  ('company_email',   'contacto@ticketvanlite.com'),
  ('ticket_note',     'Conserve este boleto. Es su seguro de viajero y comprobante de abordaje. Presentarse 15 minutos antes de la salida.');

-- 2. Terminales Base Iniciales
INSERT INTO branch (code, name, city, address, phone) VALUES
  ('CDMX-CEN', 'Terminal Central CDMX',   'Ciudad de México', 'Calz. Ignacio Zaragoza 200', '55-1000-0001'),
  ('PUE-NOR',  'Terminal Puebla Norte',   'Puebla',           'Blvd. Norte 4222',           '222-100-0002'),
  ('TLAX-CEN', 'Terminal Tlaxcala Centro','Tlaxcala',         'Av. Juárez 15',              '246-100-0003');

-- 3. Usuarios de Acceso Inicial (Contraseña por defecto: 'admin')
-- Hash generado mediante password_hash('admin', PASSWORD_DEFAULT)
INSERT INTO `user` (branch_id, role, name, username, email, password_hash) VALUES
  (NULL, 'admin',  'Administrador del Sistema', 'admin',  'admin@ticketvanlite.com',  '$2y$10$X72H1ZxDtMu5KwWJjzuCaeTkyeX8O0I4611U7G.Ny3osq78wkByLa'),
  (1,    'seller', 'Ana López (Taquilla 1)',   'ana',    'ana.taquilla@ticketvanlite.com', '$2y$10$X72H1ZxDtMu5KwWJjzuCaeTkyeX8O0I4611U7G.Ny3osq78wkByLa'),
  (2,    'seller', 'Carlos Ramírez (Taquilla 2)','carlos','carlos.puebla@ticketvanlite.com', '$2y$10$X72H1ZxDtMu5KwWJjzuCaeTkyeX8O0I4611U7G.Ny3osq78wkByLa');

-- 4. Flota Inicial de Vehículos (Vans de 14 y 18 asientos)
INSERT INTO vehicle (plate, name, type, total_seat) VALUES
  ('VAN-101', 'Van Hiace #01', 'van', 14),
  ('VAN-102', 'Van Hiace #02', 'van', 14),
  ('VAN-201', 'Van Sprinter #03', 'van', 18);
