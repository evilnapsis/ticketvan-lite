-- =============================================================================
-- PROYECTO: TicketVan Lite (2026)
-- ARCHIVO: demo_data.sql
-- PROPÓSITO: Carga de datos de demostración realistas con distribución de los
--            últimos 30 días para poblar gráficas estadísticas, KPIs y reportes.
-- =============================================================================

USE ticketvan_lite;

-- -----------------------------------------------------------------------------
-- 1. TERMINALES Y SUCURSALES (Catálogo organizacional - 6 Terminales)
-- -----------------------------------------------------------------------------
INSERT INTO branch (code, name, city, address, phone) VALUES
  ('PACH-CEN', 'Terminal Pachuca Centro',    'Pachuca',          'Av. Revolución 102',        '771-100-0004'),
  ('CUER-SUR', 'Terminal Cuernavaca Centro', 'Cuernavaca',       'Av. Morelos 88',            '777-100-0005'),
  ('QRO-NOR',  'Terminal Querétaro Norte',   'Querétaro',        'Prol. Corregidora Norte 30','442-100-0006')
ON DUPLICATE KEY UPDATE name=name;

-- -----------------------------------------------------------------------------
-- 2. FLOTA DE VEHÍCULOS (Vans y Minibuses - 7 Unidades)
-- -----------------------------------------------------------------------------
INSERT INTO vehicle (plate, name, type, total_seat) VALUES
  ('VAN-103', 'Van Hiace #03',     'van', 14),
  ('VAN-202', 'Van Sprinter #04',  'van', 18),
  ('BUS-301', 'Minibús Crafter #05','bus', 22),
  ('BUS-302', 'Minibús Crafter #06','bus', 22)
ON DUPLICATE KEY UPDATE plate=plate;

-- -----------------------------------------------------------------------------
-- 3. PADRÓN DE PASAJEROS FRECUENTES (12 Pasajeros)
-- -----------------------------------------------------------------------------
INSERT INTO passenger (first_name, last_name, document_number, phone, email) VALUES
  ('Roberto',   'Mendoza Ruiz',     'INE-84920192', '55-1234-5678', 'roberto.mendoza@gmail.com'),
  ('Gabriela',  'Soto Morales',     'INE-39102948', '222-987-6543', 'gaby.soto@hotmail.com'),
  ('Fernando',  'Vargas Cruz',      'INE-10293847', '771-456-7890', 'fer.vargas@outlook.com'),
  ('Mariana',   'Ortega Gómez',     'INE-58291039', '55-2345-6789', 'mariana.ortega@yahoo.com'),
  ('Alejandro', 'Peña Castillo',    'INE-74829104', '777-321-6549', 'ale.pena@gmail.com'),
  ('Sofia',     'Torres Valencia',  'INE-92019284', '442-789-1234', 'sofia.torres@empresa.mx'),
  ('David',     'Salazar Flores',   'INE-48291029', '55-3456-7890', 'david.salazar@live.com'),
  ('Patricia',  'Navarro Delgado',  'INE-29103948', '222-654-9871', 'paty.navarro@gmail.com'),
  ('Javier',    'Mercado Rosas',    'INE-83920194', '246-321-4567', 'javier.mercado@gmail.com'),
  ('Carmen',    'Ibáñez Estrada',   'INE-19283746', '55-4567-8901', 'carmen.ibanez@prodigy.net.mx'),
  ('Eduardo',   'Lara Benítez',     'INE-67482910', '771-890-1234', 'eduardo.lara@outlook.com'),
  ('Verónica',  'Guerrero Silva',   'INE-38291049', '442-456-7891', 'vero.guerrero@gmail.com');

-- -----------------------------------------------------------------------------
-- 4. SALIDAS / CORRIDAS (Últimos 30 días, hoy y próximas salidas)
--    Origen, Destino, Vehículo, Fecha/Hora y Precio único directo en trip
-- -----------------------------------------------------------------------------
INSERT INTO trip (id, origin_branch_id, destination_branch_id, vehicle_id, departure_at, price, status, notes) VALUES
  -- Hace 28 días
  (1,  1, 2, 1, DATE_SUB(NOW(), INTERVAL 28 DAY), 220.00, 'completed', 'Salida puntual'),
  -- Hace 25 días
  (2,  2, 1, 2, DATE_SUB(NOW(), INTERVAL 25 DAY), 220.00, 'completed', 'Sin novedades'),
  -- Hace 21 días
  (3,  1, 3, 3, DATE_SUB(NOW(), INTERVAL 21 DAY), 180.00, 'completed', 'Clima despejado'),
  -- Hace 18 días
  (4,  1, 2, 1, DATE_SUB(NOW(), INTERVAL 18 DAY), 220.00, 'completed', 'Salida con alta ocupación'),
  -- Hace 14 días
  (5,  1, 4, 4, DATE_SUB(NOW(), INTERVAL 14 DAY), 150.00, 'completed', 'Corrida regular'),
  -- Hace 10 días
  (6,  1, 2, 2, DATE_SUB(NOW(), INTERVAL 10 DAY), 220.00, 'completed', 'Tráfico ligero en autopista'),
  -- Hace 7 días
  (7,  2, 1, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), 220.00, 'completed', 'Salida en horario'),
  -- Hace 5 días
  (8,  1, 3, 3, DATE_SUB(NOW(), INTERVAL 5 DAY), 180.00, 'completed', 'Servicio exprés'),
  -- Hace 3 días
  (9,  1, 2, 4, DATE_SUB(NOW(), INTERVAL 3 DAY), 220.00, 'completed', 'Cupo lleno'),
  -- Hace 2 días
  (10, 1, 4, 5, DATE_SUB(NOW(), INTERVAL 2 DAY), 150.00, 'completed', 'Unidad limpia y desinfectada'),
  -- Hace 1 día (Ayer)
  (11, 1, 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 220.00, 'completed', 'Puntualidad 100%'),
  (12, 2, 1, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 220.00, 'completed', 'Sin contratiempos'),
  -- Hoy (Salidas activas y programadas)
  (13, 1, 2, 1, DATE_ADD(DATE(NOW()), INTERVAL '08:00:00' HOUR_SECOND), 220.00, 'completed', 'Salida matutina'),
  (14, 1, 2, 2, DATE_ADD(DATE(NOW()), INTERVAL '12:30:00' HOUR_SECOND), 220.00, 'boarding',  'Unidad en andén 1'),
  (15, 2, 1, 3, DATE_ADD(DATE(NOW()), INTERVAL '15:00:00' HOUR_SECOND), 220.00, 'scheduled', 'Venta abierta en taquilla'),
  (16, 1, 3, 4, DATE_ADD(DATE(NOW()), INTERVAL '17:30:00' HOUR_SECOND), 180.00, 'scheduled', 'Servicio vespertino'),
  (17, 1, 4, 5, DATE_ADD(DATE(NOW()), INTERVAL '19:00:00' HOUR_SECOND), 150.00, 'scheduled', 'Última salida del día'),
  -- Mañana (Programadas)
  (18, 1, 2, 1, DATE_ADD(DATE(NOW()), INTERVAL '1 08:30:00' DAY_SECOND), 220.00, 'scheduled', 'Programada en sistema'),
  (19, 2, 1, 2, DATE_ADD(DATE(NOW()), INTERVAL '1 11:00:00' DAY_SECOND), 220.00, 'scheduled', 'Programada en sistema')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- -----------------------------------------------------------------------------
-- 5. BOLETOS EMITIDOS (42 Boletos distribuidos en los últimos 30 días)
-- -----------------------------------------------------------------------------
INSERT INTO ticket (trip_id, seat_number, passenger_id, passenger_name, passenger_phone, price, payment_method, status, user_id, created_at) VALUES
  -- Trip 1 (Hace 28 días)
  (1, 1, 1, 'Roberto Mendoza Ruiz',    '55-1234-5678', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 28 DAY)),
  (1, 2, 2, 'Gabriela Soto Morales',   '222-987-6543', 220.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 28 DAY)),
  (1, 3, 3, 'Fernando Vargas Cruz',    '771-456-7890', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 28 DAY)),

  -- Trip 2 (Hace 25 días)
  (2, 1, 4, 'Mariana Ortega Gómez',    '55-2345-6789', 220.00, 'card',     'sold', 3, DATE_SUB(NOW(), INTERVAL 25 DAY)),
  (2, 2, 5, 'Alejandro Peña Castillo', '777-321-6549', 220.00, 'cash',     'sold', 3, DATE_SUB(NOW(), INTERVAL 25 DAY)),

  -- Trip 3 (Hace 21 días)
  (3, 1, 6, 'Sofia Torres Valencia',   '442-789-1234', 180.00, 'transfer', 'sold', 2, DATE_SUB(NOW(), INTERVAL 21 DAY)),
  (3, 2, 7, 'David Salazar Flores',    '55-3456-7890', 180.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 21 DAY)),
  (3, 3, 8, 'Patricia Navarro Delgado','222-654-9871', 180.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 21 DAY)),

  -- Trip 4 (Hace 18 días)
  (4, 1, 9, 'Javier Mercado Rosas',    '246-321-4567', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 18 DAY)),
  (4, 2, 10,'Carmen Ibáñez Estrada',   '55-4567-8901', 220.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 18 DAY)),
  (4, 3, 11,'Eduardo Lara Benítez',    '771-890-1234', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 18 DAY)),

  -- Trip 5 (Hace 14 días)
  (5, 1, 12,'Verónica Guerrero Silva', '442-456-7891', 150.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 14 DAY)),
  (5, 2, NULL, 'Luis Alberto Domínguez','55-9988-7766', 150.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 14 DAY)),
  (5, 3, NULL, 'Mónica Paredes Tapia',  '55-1122-3344', 150.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 14 DAY)),

  -- Trip 6 (Hace 10 días)
  (6, 1, 1, 'Roberto Mendoza Ruiz',    '55-1234-5678', 220.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (6, 2, 2, 'Gabriela Soto Morales',   '222-987-6543', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (6, 3, 3, 'Fernando Vargas Cruz',    '771-456-7890', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 10 DAY)),

  -- Trip 7 (Hace 7 días)
  (7, 1, 4, 'Mariana Ortega Gómez',    '55-2345-6789', 220.00, 'transfer', 'sold', 3, DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (7, 2, 5, 'Alejandro Peña Castillo', '777-321-6549', 220.00, 'cash',     'sold', 3, DATE_SUB(NOW(), INTERVAL 7 DAY)),

  -- Trip 8 (Hace 5 días)
  (8, 1, 6, 'Sofia Torres Valencia',   '442-789-1234', 180.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (8, 2, 7, 'David Salazar Flores',    '55-3456-7890', 180.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (8, 3, 8, 'Patricia Navarro Delgado','222-654-9871', 180.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),

  -- Trip 9 (Hace 3 días)
  (9, 1, 9, 'Javier Mercado Rosas',    '246-321-4567', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (9, 2, 10,'Carmen Ibáñez Estrada',   '55-4567-8901', 220.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (9, 3, 11,'Eduardo Lara Benítez',    '771-890-1234', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),

  -- Trip 10 (Hace 2 días)
  (10, 1, 12,'Verónica Guerrero Silva','442-456-7891', 150.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (10, 2, NULL, 'Andrea Montesinos',   '771-555-1212', 150.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),

  -- Trip 11 (Ayer)
  (11, 1, 1, 'Roberto Mendoza Ruiz',   '55-1234-5678', 220.00, 'card',     'sold', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (11, 2, 2, 'Gabriela Soto Morales',  '222-987-6543', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (11, 3, 3, 'Fernando Vargas Cruz',   '771-456-7890', 220.00, 'transfer', 'sold', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (11, 4, 4, 'Mariana Ortega Gómez',   '55-2345-6789', 220.00, 'cash',     'sold', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),

  -- Trip 12 (Ayer)
  (12, 1, 5, 'Alejandro Peña Castillo','777-321-6549', 220.00, 'card',     'sold', 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (12, 2, 6, 'Sofia Torres Valencia',  '442-789-1234', 220.00, 'cash',     'sold', 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),

  -- Trip 13 (Hoy matutino)
  (13, 1, 7, 'David Salazar Flores',   '55-3456-7890', 220.00, 'cash',     'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '07:35:00' HOUR_SECOND)),
  (13, 2, 8, 'Patricia Navarro Delgado','222-654-9871', 220.00, 'card',    'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '07:42:00' HOUR_SECOND)),
  (13, 3, 9, 'Javier Mercado Rosas',   '246-321-4567', 220.00, 'cash',     'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '07:48:00' HOUR_SECOND)),
  (13, 4, 10,'Carmen Ibáñez Estrada',  '55-4567-8901', 220.00, 'cash',     'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '07:55:00' HOUR_SECOND)),

  -- Trip 14 (Hoy abordando)
  (14, 1, 11,'Eduardo Lara Benítez',   '771-890-1234', 220.00, 'card',     'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '11:50:00' HOUR_SECOND)),
  (14, 2, 12,'Verónica Guerrero Silva','442-456-7891', 220.00, 'cash',     'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '12:05:00' HOUR_SECOND)),
  (14, 3, NULL,'Héctor Manuel Ponce',  '55-3344-5566', 220.00, 'transfer', 'sold', 2, DATE_ADD(DATE(NOW()), INTERVAL '12:15:00' HOUR_SECOND)),

  -- Trip 15 (Hoy por la tarde)
  (15, 1, 1, 'Roberto Mendoza Ruiz',   '55-1234-5678', 220.00, 'card',     'sold', 3, DATE_ADD(DATE(NOW()), INTERVAL '10:20:00' HOUR_SECOND)),
  (15, 2, 2, 'Gabriela Soto Morales',  '222-987-6543', 220.00, 'cash',     'sold', 3, DATE_ADD(DATE(NOW()), INTERVAL '11:10:00' HOUR_SECOND));
